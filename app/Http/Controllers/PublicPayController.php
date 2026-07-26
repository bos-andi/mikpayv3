<?php

namespace App\Http\Controllers;

use App\Models\BillingCustomer;
use App\Models\BillingPaymentLink;
use App\Models\Tenant;
use App\Services\PaymentLinkService;
use App\Services\XenditService;
use App\Support\BillingArrearsHelper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPayController extends Controller
{
    public function show(string $token): View
    {
        $link = $this->findLink($token);
        $tenant = $link->tenant ?: current_tenant();

        if ($link->isExpired() && $link->status === 'pending') {
            $link->forceFill(['status' => 'expired'])->save();
        }

        $phoneHint = $this->phoneHint($link->phone);
        $periodItems = [];
        $isolirInfo = null;
        $customer = null;

        if ($tenant instanceof Tenant && $tenant->id) {
            $customer = BillingCustomer::query()
                ->where('tenant_id', $tenant->id)
                ->where('router_id', $link->router_id)
                ->where('customer_name', $link->customer_name)
                ->first();
        }

        if ($customer && $tenant instanceof Tenant) {
            $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
                $customer->customer_name,
                (int) $customer->due_day,
                (int) $customer->monthly_fee,
                (int) $link->router_id,
                $customer->started_at,
                $tenant->isolirGraceDays()
            );

            $shortfall = $arrears['shortfall_by_period'] ?? [];
            foreach ($arrears['unpaid_periods'] ?? [] as $i => $period) {
                $amount = (int) ($shortfall[$period] ?? $customer->monthly_fee ?? 0);
                if ($amount <= 0) {
                    continue;
                }
                $periodItems[] = [
                    'period' => $period,
                    'label' => $arrears['period_labels'][$i] ?? Carbon::parse($period.'-01')->translatedFormat('M Y'),
                    'amount' => $amount,
                ];
            }

            $livePeriods = array_column($periodItems, 'period');
            $liveAmount = array_sum(array_column($periodItems, 'amount'));

            // Buka ulang link jika masih ada tunggakan (mis. setelah bayar parsial).
            // Jangan sentuh invoice Xendit yang masih aktif.
            if ($periodItems !== [] && ! $link->isExpired()) {
                if ($link->status === 'paid') {
                    $link->forceFill([
                        'status' => 'pending',
                        'paid_at' => null,
                        'periods' => $livePeriods,
                        'amount' => $liveAmount,
                        'xendit_invoice_id' => null,
                        'xendit_external_id' => null,
                        'xendit_invoice_url' => null,
                        'payment_checkout' => null,
                    ])->save();
                } elseif ($link->status === 'pending' && ! $link->hasActiveCheckout() && ! $link->xendit_invoice_url) {
                    $sameCatalog = json_encode(array_values($link->periods ?? [])) === json_encode(array_values($livePeriods))
                        && (int) $link->amount === $liveAmount;
                    if (! $sameCatalog) {
                        $link->forceFill([
                            'periods' => $livePeriods,
                            'amount' => $liveAmount,
                        ])->save();
                    }
                }
            }

            if ($periodItems !== [] && ($arrears['unpaid_count'] ?? 0) > 0) {
                $days = $arrears['days_to_isolir'];
                $past = (int) ($arrears['unpaid_past_count'] ?? 0) > 0;
                if ($past) {
                    $label = 'Siap dinonaktifkan otomatis (ada tunggakan bulan lalu)';
                } elseif ($days !== null && $days > 0) {
                    $label = $days.' hari lagi siap dinonaktifkan otomatis';
                } elseif ($days === 0) {
                    $label = 'Siap dinonaktifkan otomatis hari ini';
                } else {
                    $label = 'Lewat masa tenggang — dapat dinonaktifkan otomatis';
                }

                $isolirInfo = [
                    'days' => $days,
                    'date' => $arrears['isolir_date'],
                    'label' => $label,
                    'grace_days' => $tenant->isolirGraceDays(),
                    'due_day' => (int) $customer->due_day,
                    'should_isolir' => (bool) ($arrears['should_isolir'] ?? false),
                ];
            } elseif ($periodItems === [] && $link->status === 'pending') {
                $link->forceFill([
                    'status' => 'paid',
                    'paid_at' => $link->paid_at ?: now(),
                    'amount' => 0,
                    'periods' => [],
                ])->save();
            }
        }

        return view('public.pay', [
            'link' => $link->fresh(),
            'tenant' => $tenant,
            'periodItems' => $periodItems,
            'periodLabels' => array_column($periodItems, 'label'),
            'phoneHint' => $phoneHint,
            'statusQuery' => request('status'),
            'xenditReady' => $tenant instanceof Tenant && $tenant->id && \App\Support\PaymentGateway::xenditReady($tenant),
            'gatewaySource' => $tenant instanceof Tenant ? \App\Support\PaymentGateway::credentialSourceLabel($tenant) : null,
            'isolirInfo' => $isolirInfo,
        ]);
    }

    public function checkout(string $token, Request $request): RedirectResponse
    {
        $link = $this->findLink($token);
        $tenant = $link->tenant;
        if (! $tenant) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        if (! \App\Support\PaymentGateway::xenditReady($tenant)) {
            return back()->with('error', 'Pembayaran online belum tersedia.');
        }

        if ($link->isExpired()) {
            return back()->with('error', 'Link sudah tidak berlaku atau sudah lunas.');
        }

        $customer = BillingCustomer::query()
            ->where('tenant_id', $tenant->id)
            ->where('router_id', $link->router_id)
            ->where('customer_name', $link->customer_name)
            ->first();

        if (! $customer) {
            return back()->with('error', 'Data pelanggan tidak ditemukan.');
        }

        $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
            $customer->customer_name,
            (int) $customer->due_day,
            (int) $customer->monthly_fee,
            (int) $link->router_id,
            $customer->started_at,
            $tenant->isolirGraceDays()
        );

        $unpaid = array_values($arrears['unpaid_periods'] ?? []);
        $shortfall = $arrears['shortfall_by_period'] ?? [];

        if ($unpaid === []) {
            return back()->with('error', 'Tidak ada tunggakan yang perlu dibayar.');
        }

        $data = $request->validate([
            'periods' => ['required', 'array', 'min:1'],
            'periods.*' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ], [], [
            'periods' => 'Periode',
        ]);

        $selected = array_values(array_unique($data['periods']));
        sort($selected);

        $prefix = array_slice($unpaid, 0, count($selected));
        if ($selected !== $prefix) {
            return back()->with('error', 'Periode harus berurutan dari bulan tertua. Tidak boleh loncat.');
        }

        $amount = 0;
        foreach ($selected as $period) {
            $amount += (int) ($shortfall[$period] ?? 0);
        }

        if ($amount <= 0) {
            return back()->with('error', 'Nominal pembayaran tidak valid.');
        }

        $sameSelection = json_encode(array_values($link->periods ?? [])) === json_encode($selected)
            && (int) $link->amount === $amount;

        if ($sameSelection && $link->hasActiveCheckout()) {
            return redirect()->to(url('/bayar/'.$link->token.'/instruksi'));
        }

        $link->forceFill([
            'status' => 'pending',
            'paid_at' => null,
            'periods' => $selected,
            'amount' => $amount,
            'xendit_invoice_id' => null,
            'xendit_external_id' => null,
            'xendit_invoice_url' => null,
            'payment_checkout' => null,
        ])->save();

        return redirect()->to(url('/bayar/'.$link->token.'/metode'));
    }

    public function methods(string $token): View|RedirectResponse
    {
        $link = $this->findLink($token);
        $tenant = $link->tenant ?: current_tenant();

        if ($link->isExpired() || ! $link->isPayable()) {
            return redirect()->to(url('/bayar/'.$link->token))->with('error', 'Link tidak berlaku.');
        }

        if (request()->boolean('ganti') && $link->payment_checkout) {
            $link->forceFill([
                'payment_checkout' => null,
                'xendit_invoice_id' => null,
                'xendit_external_id' => null,
                'xendit_invoice_url' => null,
            ])->save();
        } elseif ($link->hasActiveCheckout()) {
            return redirect()->to(url('/bayar/'.$link->token.'/instruksi'));
        }

        $periodLabels = array_map(
            fn ($p) => Carbon::parse($p.'-01')->translatedFormat('M Y'),
            is_array($link->periods) ? $link->periods : []
        );

        return view('public.pay-methods', [
            'link' => $link,
            'tenant' => $tenant,
            'periodLabels' => $periodLabels,
            'vaBanks' => XenditService::VA_BANKS,
            'xenditReady' => $tenant instanceof Tenant && \App\Support\PaymentGateway::xenditReady($tenant),
        ]);
    }

    public function createMethod(string $token, Request $request, XenditService $xendit): RedirectResponse
    {
        $link = $this->findLink($token);
        $tenant = $link->tenant;
        if (! $tenant) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        if (! \App\Support\PaymentGateway::xenditReady($tenant)) {
            return back()->with('error', 'Pembayaran online belum tersedia.');
        }

        if ($link->isExpired() || ! $link->isPayable()) {
            return redirect()->to(url('/bayar/'.$link->token))->with('error', 'Link tidak berlaku.');
        }

        $data = $request->validate([
            'method' => ['required', 'in:qris,va'],
            'bank' => ['nullable', 'string', 'in:'.implode(',', XenditService::VA_BANKS)],
        ], [], [
            'method' => 'Metode',
            'bank' => 'Bank',
        ]);

        if ($data['method'] === 'va' && empty($data['bank'])) {
            return back()->with('error', 'Pilih bank untuk Virtual Account.');
        }

        $callbackUrl = url('/webhooks/xendit');

        $result = $data['method'] === 'qris'
            ? $xendit->createQris($tenant, $link, $callbackUrl)
            : $xendit->createVirtualAccount($tenant, $link, (string) $data['bank'], $callbackUrl);

        if (! $result['ok']) {
            return back()->with('error', $result['message'] ?: 'Gagal membuat pembayaran.');
        }

        return redirect()->to(url('/bayar/'.$link->token.'/instruksi'));
    }

    public function instruction(string $token): View|RedirectResponse
    {
        $link = $this->findLink($token);
        $tenant = $link->tenant ?: current_tenant();

        if ($link->status === 'paid') {
            return redirect()->to(url('/bayar/'.$link->token).'?status=success');
        }

        if (! $link->hasActiveCheckout()) {
            return redirect()->to(url('/bayar/'.$link->token.'/metode'))
                ->with('error', 'Pilih metode pembayaran dulu.');
        }

        $checkout = $link->payment_checkout;
        $periodLabels = array_map(
            fn ($p) => Carbon::parse($p.'-01')->translatedFormat('M Y'),
            is_array($link->periods) ? $link->periods : []
        );

        return view('public.pay-instruction', [
            'link' => $link,
            'tenant' => $tenant,
            'checkout' => $checkout,
            'periodLabels' => $periodLabels,
            'statusUrl' => url('/bayar/'.$link->token.'/status'),
        ]);
    }

    public function status(string $token): JsonResponse
    {
        $link = $this->findLink($token);
        $checkout = is_array($link->payment_checkout) ? $link->payment_checkout : [];
        $expiredCheckout = false;
        if (! empty($checkout['expires_at'])) {
            try {
                $expiredCheckout = now()->greaterThan(Carbon::parse($checkout['expires_at']));
            } catch (\Throwable) {
                $expiredCheckout = false;
            }
        }

        return response()->json([
            'ok' => true,
            'status' => $link->status,
            'paid' => $link->status === 'paid',
            'expired' => $link->isExpired() || $expiredCheckout,
            'amount' => (int) $link->amount,
            'redirect' => $link->status === 'paid'
                ? url('/bayar/'.$link->token).'?status=success'
                : null,
        ]);
    }

    public function lookupForm(): View
    {
        $tenant = current_tenant();

        return view('public.pay-lookup', [
            'tenant' => $tenant,
        ]);
    }

    public function lookup(Request $request, PaymentLinkService $links): RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant?->id) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $data = $request->validate([
            'public_code' => ['required', 'string', 'max:16'],
            'phone_last4' => ['required', 'string', 'size:4', 'regex:/^\d{4}$/'],
        ], [], [
            'public_code' => 'Kode pelanggan',
            'phone_last4' => '4 digit akhir HP',
        ]);

        $customer = BillingCustomer::query()
            ->where('tenant_id', $tenant->id)
            ->whereRaw('UPPER(public_code) = ?', [strtoupper($data['public_code'])])
            ->first();

        if (! $customer) {
            return back()->withInput()->with('error', 'Kode pelanggan tidak ditemukan.');
        }

        $phoneDigits = preg_replace('/\D+/', '', (string) $customer->phone) ?: '';
        if ($phoneDigits === '' || ! str_ends_with($phoneDigits, $data['phone_last4'])) {
            return back()->withInput()->with('error', 'Verifikasi nomor HP tidak cocok.');
        }

        $router = $customer->router;
        if (! $router) {
            return back()->withInput()->with('error', 'Router pelanggan tidak ditemukan.');
        }

        try {
            $link = $links->createOrRefresh($tenant, $router, $customer->customer_name);
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->to('/bayar/'.$link->token);
    }

    protected function findLink(string $token): BillingPaymentLink
    {
        $link = BillingPaymentLink::query()
            ->with('tenant')
            ->where('token', $token)
            ->first();

        abort_unless($link, 404, 'Link pembayaran tidak ditemukan.');

        $tenant = current_tenant();
        if ($tenant?->id && $this->isTenantHost(request()) && (int) $link->tenant_id !== (int) $tenant->id) {
            abort(404, 'Link pembayaran tidak ditemukan.');
        }

        return $link;
    }

    protected function isTenantHost(Request $request): bool
    {
        $host = $request->getHost();
        $base = config('mikpay.tenant_base_domain');
        if (! $base || ! str_ends_with($host, '.'.$base)) {
            return false;
        }
        $subdomain = substr($host, 0, -strlen('.'.$base));
        $reserved = ['www', 'app', 'user', 'mail', 'api', 'admin', 'remote', 'cdn', 'static'];

        return $subdomain && ! in_array($subdomain, $reserved, true);
    }

    protected function phoneHint(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?: '';
        if (strlen($digits) < 4) {
            return null;
        }

        return '****'.substr($digits, -4);
    }
}
