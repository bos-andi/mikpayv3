<?php

namespace App\Http\Controllers;

use App\Models\BillingCustomer;
use App\Models\BillingPayment;
use App\Services\AutoIsolirService;
use App\Services\MikrotikService;
use App\Services\PaymentLinkService;
use App\Support\ActiveRouter;
use App\Support\BillingArrearsHelper;
use App\Support\BillingStatusHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request, MikrotikService $mikrotik): View|RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $result = $mikrotik->fetchPppCustomers($router);
        $billingMap = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->get()
            ->keyBy(fn (BillingCustomer $b) => strtolower($b->customer_name));

        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        $period = now()->format('Y-m');
        $tenant = current_tenant();
        $grace = $tenant?->isolirGraceDays() ?? (int) config('mikpay.billing.grace_days', 2);

        $rows = collect($result['customers'])->map(function (array $c) use ($billingMap, $router, $grace, $period) {
            $billing = $billingMap->get(strtolower($c['name']));
            $dueDay = (int) ($billing?->due_day ?? 0);
            $fee = (int) ($billing?->monthly_fee ?? 0);
            $status = BillingStatusHelper::getStatus($c['name'], $dueDay, $router->id, $fee);
            $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
                $c['name'],
                $dueDay,
                $fee,
                $router->id,
                $billing?->started_at,
                $grace
            );

            return [
                'id' => $c['id'] ?? '',
                'name' => $c['name'],
                'display_name' => $billing?->display_name,
                'phone' => $billing?->phone,
                'zone' => $billing?->zone,
                'package_label' => $billing?->package_label ?: ($c['profile'] ?? null),
                'monthly_fee' => $fee,
                'due_day' => $dueDay,
                'online' => $c['online'] ?? false,
                'isolir' => $c['isolir'] ?? false,
                'disabled' => $c['disabled'] ?? false,
                'status' => $status['status'],
                'status_label' => $status['status_label'],
                'message' => $status['message'],
                'due_date' => $status['due_date'],
                'due_date_label' => BillingStatusHelper::formatDueDate($status['due_date']),
                'paid_amount' => $status['paid_amount'],
                'remaining' => max(0, $fee - (int) $status['paid_amount']),
                'billing_configured' => $billing?->isBillingConfigured() ?? false,
                'arrears_label' => $arrears['label'],
                'arrears_tooltip' => $arrears['tooltip'],
                'arrears_periods' => $arrears['unpaid_periods'],
                'arrears_period_labels' => $arrears['period_labels'],
                'arrears_count' => $arrears['unpaid_count'],
                'arrears_past_count' => $arrears['unpaid_past_count'],
                'outstanding_total' => $arrears['outstanding_total'],
                'should_isolir' => $arrears['should_isolir'],
                'days_to_isolir' => $arrears['days_to_isolir'],
                'isolir_label' => $arrears['isolir_label'],
                'first_unpaid_period' => $arrears['unpaid_periods'][0] ?? $period,
                'public_code' => $billing?->public_code,
            ];
        });

        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(function (array $r) use ($needle) {
                $hay = mb_strtolower(implode(' ', [
                    $r['name'], $r['display_name'] ?? '', $r['phone'] ?? '', $r['zone'] ?? '', $r['package_label'] ?? '',
                    $r['arrears_tooltip'] ?? '',
                ]));

                return str_contains($hay, $needle);
            })->values();
        }

        if ($filter === 'menunggak') {
            $rows = $rows->filter(fn ($r) => ($r['arrears_past_count'] ?? 0) > 0 || ($r['outstanding_total'] ?? 0) > 0)->values();
        } elseif ($filter !== 'all') {
            $rows = $rows->where('status', $filter)->values();
        }

        $priority = ['overdue' => 1, 'due_today' => 2, 'partial' => 3, 'due_soon' => 4, 'waiting' => 5, 'paid' => 6, 'not_set' => 7];
        $rows = $rows->sortBy(function ($r) use ($priority) {
            $arrearsBoost = ($r['arrears_past_count'] ?? 0) > 0 ? '0' : '1';

            return $arrearsBoost.'|'.($priority[$r['status']] ?? 99).'|'.mb_strtolower($r['name']);
        })->values();

        $allRows = collect($result['customers'])->map(function (array $c) use ($billingMap, $router, $grace) {
            $billing = $billingMap->get(strtolower($c['name']));
            $dueDay = (int) ($billing?->due_day ?? 0);
            $fee = (int) ($billing?->monthly_fee ?? 0);
            $status = BillingStatusHelper::getStatus($c['name'], $dueDay, $router->id, $fee);
            $arrears = BillingArrearsHelper::getUnpaidPeriodsSummary(
                $c['name'],
                $dueDay,
                $fee,
                $router->id,
                $billing?->started_at,
                $grace
            );

            return [
                'status' => $status['status'],
                'arrears_past_count' => $arrears['unpaid_past_count'],
                'outstanding_total' => $arrears['outstanding_total'],
            ];
        });

        $stats = [
            'total' => $allRows->count(),
            'overdue' => $allRows->filter(fn ($s) => $s['status'] === 'overdue')->count(),
            'due_today' => $allRows->filter(fn ($s) => $s['status'] === 'due_today')->count(),
            'paid' => $allRows->filter(fn ($s) => $s['status'] === 'paid')->count(),
            'not_set' => $allRows->filter(fn ($s) => $s['status'] === 'not_set')->count(),
            'menunggak' => $allRows->filter(fn ($s) => ($s['arrears_past_count'] ?? 0) > 0 || ($s['outstanding_total'] ?? 0) > 0)->count(),
            'outstanding_total' => (int) $allRows->sum('outstanding_total'),
            'period_income' => (int) BillingPayment::query()
                ->where('router_id', $router->id)
                ->where('period', $period)
                ->sum('amount'),
        ];

        $recentPayments = BillingPayment::query()
            ->where('router_id', $router->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('panel.billing', [
            'router' => $router,
            'rows' => $rows,
            'stats' => $stats,
            'period' => $period,
            'q' => $q,
            'filter' => $filter,
            'fetchOk' => $result['ok'],
            'fetchMessage' => $result['message'],
            'recentPayments' => $recentPayments,
            'trackingHint' => ! BillingArrearsHelper::hasExplicitTrackingStart(),
        ]);
    }

    public function storePayment(Request $request, AutoIsolirService $autoIsolir): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            abort(422, 'Tenant belum tersedia.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'period' => ['required', 'string', 'size:7', 'regex:/^\d{4}-\d{2}$/'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:Tunai,Transfer Manual'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [], [
            'customer_name' => 'Username PPP',
            'amount' => 'Nominal',
            'period' => 'Periode',
            'payment_date' => 'Tanggal bayar',
            'payment_method' => 'Metode',
            'notes' => 'Catatan',
        ]);

        $customer = BillingCustomer::query()
            ->where('router_id', $router->id)
            ->where('customer_name', $data['customer_name'])
            ->first();

        $monthlyFee = (int) ($customer?->monthly_fee ?? 0);
        $periodPaid = (int) BillingPayment::query()
            ->where('router_id', $router->id)
            ->where('customer_name', $data['customer_name'])
            ->where('period', $data['period'])
            ->sum('amount');

        $currentPaid = $periodPaid + (int) $data['amount'];
        $paymentStatus = ($monthlyFee > 0 && $currentPaid < $monthlyFee) ? 'partial' : 'paid';
        $remaining = max(0, $monthlyFee - $currentPaid);

        $notes = trim((string) ($data['notes'] ?? ''));
        if ($remaining > 0) {
            $carry = 'Sisa tagihan periode ini: Rp '.number_format($remaining, 0, ',', '.');
            $notes = $notes !== '' ? ($notes.' | '.$carry) : $carry;
        }

        $payment = BillingPayment::query()->create([
            'tenant_id' => $tenant->id,
            'router_id' => $router->id,
            'customer_name' => $data['customer_name'],
            'display_name' => $customer?->display_name,
            'phone' => $customer?->phone,
            'amount' => (int) $data['amount'],
            'remaining_amount' => $remaining,
            'period' => $data['period'],
            'payment_date' => $data['payment_date'].' '.now()->format('H:i:s'),
            'payment_method' => $data['payment_method'],
            'notes' => $notes !== '' ? $notes : null,
            'status' => $paymentStatus,
        ]);

        $payment->invoice_id = BillingStatusHelper::formatInvoiceId($payment->period, (int) $payment->id);
        $payment->save();

        $msg = $remaining > 0
            ? "Pembayaran parsial dicatat ({$payment->invoice_id}). Sisa: Rp ".number_format($remaining, 0, ',', '.')
            : "Pembayaran berhasil dicatat ({$payment->invoice_id}).";

        $sync = $autoIsolir->syncCustomerByName($router, $data['customer_name']);
        if (($sync['action'] ?? '') === 'open') {
            $msg .= ' Isolir dibuka otomatis.';
        } elseif (($sync['action'] ?? '') === 'isolir') {
            $msg .= ' Masih menunggak — tetap/auto isolir.';
        }

        return redirect()->to(panel_route('billing'))->with('success', $msg);
    }

    public function createPaymentLink(Request $request, PaymentLinkService $links): RedirectResponse|JsonResponse
    {
        $wantsJson = $request->expectsJson() || $request->ajax();

        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'Pilih router aktif dulu.'], 422);
            }

            return $router;
        }

        $tenant = current_tenant();
        if (! $tenant?->id) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => 'Tenant belum tersedia.'], 422);
            }
            abort(422, 'Tenant belum tersedia.');
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'send_wa' => ['nullable', 'boolean'],
        ]);

        try {
            $link = $links->createOrRefresh($tenant, $router, $data['customer_name']);
        } catch (\Throwable $e) {
            if ($wantsJson) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $url = $link->publicUrl();

        if ($request->boolean('send_wa')) {
            $wa = $links->sendWhatsAppReminder($tenant, $link);
            if ($wantsJson) {
                return response()->json([
                    'ok' => $wa['ok'],
                    'url' => $url,
                    'copied' => true,
                    'message' => $wa['ok']
                        ? 'Link dibuat & WA terkirim.'
                        : ('Link dibuat, tapi WA gagal: '.$wa['message']),
                    'wa_ok' => $wa['ok'],
                ], $wa['ok'] ? 200 : 422);
            }

            if ($wa['ok']) {
                return back()->with('success', 'Link bayar dikirim via WhatsApp.');
            }

            return back()
                ->with('success', 'Link dibuat.')
                ->with('error', $wa['message']);
        }

        if ($wantsJson) {
            return response()->json([
                'ok' => true,
                'url' => $url,
                'message' => 'Link bayar disalin.',
            ]);
        }

        return back()->with('success', 'Link bayar siap (disalin dari tombol link).');
    }

    public function destroyPayment(BillingPayment $payment, AutoIsolirService $autoIsolir): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        if ((int) $payment->router_id !== (int) $router->id) {
            abort(403);
        }

        $customerName = $payment->customer_name;
        $invoice = $payment->invoice_id ?: '#'.$payment->id;
        $payment->delete();

        $msg = "Pembayaran {$invoice} dihapus.";
        $sync = $autoIsolir->syncCustomerByName($router, $customerName);
        if (($sync['action'] ?? '') === 'isolir') {
            $msg .= ' Auto isolir dijalankan.';
        }

        return back()->with('success', $msg);
    }

    public function runAutoIsolir(AutoIsolirService $autoIsolir): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $summary = $autoIsolir->syncRouter($router);

        return back()->with(
            'success',
            sprintf(
                'Auto isolir selesai: %d isolir, %d dibuka, %d tanpa perubahan, %d error.',
                $summary['isolir'] ?? 0,
                $summary['open'] ?? 0,
                $summary['none'] ?? 0,
                $summary['error'] ?? 0
            )
        );
    }

    public function isolir(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        return $this->toggleIsolir($request, $mikrotik, true);
    }

    public function open(Request $request, MikrotikService $mikrotik): RedirectResponse
    {
        return $this->toggleIsolir($request, $mikrotik, false);
    }

    protected function toggleIsolir(Request $request, MikrotikService $mikrotik, bool $isolir): RedirectResponse
    {
        $router = $this->requireActiveRouter();
        if ($router instanceof RedirectResponse) {
            return $router;
        }

        $data = $request->validate([
            'id' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $result = $mikrotik->setSecretIsolir($router, $data['id'], $data['name'], $isolir);

        return back()->with(
            $result['ok'] ? 'success' : 'error',
            $result['message']
        );
    }

    protected function requireActiveRouter(): \App\Models\Router|RedirectResponse
    {
        $router = ActiveRouter::current();

        if (! $router) {
            return redirect()
                ->to(panel_route('settings').'?tab=mikrotik')
                ->with('error', 'Belum ada router MikroTik. Tambah & aktifkan router dulu.');
        }

        if (! $router->isEnabled()) {
            return redirect()
                ->to(panel_route('settings').'?tab=mikrotik')
                ->with('error', 'Router aktif dinonaktifkan. Enable atau pilih router lain.');
        }

        return $router;
    }
}
