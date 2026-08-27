<?php

namespace App\Http\Controllers;

use App\Models\PlatformPendingPayment;
use App\Models\PlatformSetting;
use App\Services\ManualPaymentGatewayService;
use App\Services\PlatformSubscriptionPaymentService;
use App\Services\SubscriptionPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public const SESSION_CHECKOUT_PACKAGE = 'langganan_checkout_package';

    public function __construct(
        private readonly ManualPaymentGatewayService $gateway,
        private readonly PlatformSubscriptionPaymentService $platformPayments,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant) {
            return redirect()->route('panel.dashboard')->with('error', 'Tenant tidak ditemukan.');
        }

        $user = $request->user();
        $packages = SubscriptionPackageService::all();
        $end = $tenant->subscriptionEndsAt();
        $now = wib_now();
        $remainingDays = null;
        if ($end) {
            // Sisa hari penuh berdasarkan tanggal & jam WIB (ceil detik → hari).
            $secs = $now->diffInSeconds($end, false);
            $remainingDays = $secs > 0 ? (int) ceil($secs / 86400) : 0;
        }

        $pendingPayments = PlatformPendingPayment::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
        $pendingLatest = $pendingPayments->firstWhere('status', 'pending');

        $status = (string) $request->query('status', '');
        if ($status === 'success' && $pendingLatest?->isXendit()) {
            // Webhook kadang telat — refresh status dari DB setelah redirect.
            $pendingLatest->refresh();
            $tenant->refresh();
        }

        return view('panel.subscription.index', [
            'tenant' => $tenant,
            'packages' => $packages,
            'paymentChannels' => $this->gateway->channels(),
            'xenditReady' => $this->platformPayments->xenditReady(),
            'isActive' => $tenant->subscriptionIsActive(),
            'remainingDays' => $remainingDays,
            'subscriptionUntil' => format_wib($end),
            'pendingPayments' => $pendingPayments,
            'hasPending' => $pendingLatest !== null,
            'pendingLatest' => $pendingLatest,
            'returnStatus' => $status,
            'subscriptionVerifyWaUrl' => ($pendingLatest && ! $pendingLatest->isXendit())
                ? $this->verificationWhatsappUrl($user, $pendingLatest)
                : null,
        ]);
    }

    public function selectPackage(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        $user = $request->user();
        if (! $tenant || ! $user) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $packages = SubscriptionPackageService::all();
        $data = $request->validate([
            'package' => ['required', 'string', Rule::in(array_keys($packages))],
        ]);

        $pkg = $packages[$data['package']];
        if (($pkg['cta_type'] ?? '') === 'whatsapp') {
            $wa = config('mikpay.whatsapp');
            $msg = (string) ($pkg['wa_message'] ?? 'Halo Admin, saya tertarik paket '.$pkg['name']);
            $url = 'https://wa.me/'.$wa['number'].'?text='.rawurlencode($msg);

            return redirect()->away($url);
        }

        // Preferensi: Xendit otomatis (platform .env)
        if ($this->platformPayments->xenditReady()) {
            $result = $this->platformPayments->startXenditCheckout($tenant, $user, $data['package'], $pkg);

            if (! empty($result['invoice_url'])) {
                return redirect()->away($result['invoice_url']);
            }

            if (! ($result['ok'] ?? false)) {
                return back()->with('error', $result['message'] ?? 'Gagal membuat invoice Xendit.');
            }

            // Fallback manual hanya jika Xendit gagal total (tanpa invoice)
            $request->session()->put(self::SESSION_CHECKOUT_PACKAGE, $data['package']);

            return redirect()->to(panel_route('subscription.checkout'))
                ->with('error', 'Xendit tidak mengembalikan link bayar. Coba lagi atau hubungi admin.');
        }

        if (PlatformPendingPayment::query()->where('tenant_id', $tenant->id)->where('status', 'pending')->exists()) {
            return back()->with('error', 'Masih ada pembayaran yang menunggu verifikasi.');
        }

        $request->session()->put(self::SESSION_CHECKOUT_PACKAGE, $data['package']);

        return redirect()->to(panel_route('subscription.checkout'));
    }

    public function cancelPending(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant) {
            return back()->with('error', 'Tenant tidak ditemukan.');
        }

        $pending = PlatformPendingPayment::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->first();

        if (! $pending) {
            return back()->with('error', 'Tidak ada pembayaran pending.');
        }

        $this->platformPayments->cancelPending($pending);
        $request->session()->forget(self::SESSION_CHECKOUT_PACKAGE);

        return back()->with('success', 'Pembayaran pending dibatalkan. Silakan pilih paket lagi.');
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $tenant = current_tenant();
        if (! $tenant) {
            return redirect()->to(panel_route('dashboard'))->with('error', 'Tenant tidak ditemukan.');
        }

        $packageKey = (string) $request->session()->get(self::SESSION_CHECKOUT_PACKAGE, '');
        $packages = SubscriptionPackageService::all();

        if ($packageKey === '' || ! isset($packages[$packageKey])) {
            return redirect()->to(panel_route('subscription'))
                ->with('error', 'Pilih paket terlebih dahulu.');
        }

        if (PlatformPendingPayment::query()->where('tenant_id', $tenant->id)->where('status', 'pending')->exists()) {
            $request->session()->forget(self::SESSION_CHECKOUT_PACKAGE);

            return redirect()->to(panel_route('subscription'))
                ->with('error', 'Masih ada pembayaran yang menunggu verifikasi.');
        }

        return view('panel.subscription.checkout', [
            'tenant' => $tenant,
            'selectedPackageKey' => $packageKey,
            'selectedPackage' => $packages[$packageKey],
            'paymentChannels' => $this->gateway->channels(),
            'xenditReady' => $this->platformPayments->xenditReady(),
        ]);
    }

    public function cancelCheckout(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_CHECKOUT_PACKAGE);

        return redirect()->to(panel_route('subscription'))->with('success', 'Pemilihan paket dibatalkan.');
    }

    public function requestPayment(Request $request): RedirectResponse
    {
        $tenant = current_tenant();
        $user = $request->user();
        if (! $tenant || ! $user) {
            return back()->with('error', 'Sesi tidak valid.');
        }

        // Jika Xendit ready, arahkan ulang ke alur otomatis
        if ($this->platformPayments->xenditReady()) {
            $packageKey = (string) $request->input('package', $request->session()->get(self::SESSION_CHECKOUT_PACKAGE, ''));
            $packages = SubscriptionPackageService::all();
            if ($packageKey !== '' && isset($packages[$packageKey])) {
                $result = $this->platformPayments->startXenditCheckout($tenant, $user, $packageKey, $packages[$packageKey]);
                if (($result['ok'] ?? false) && ! empty($result['invoice_url'])) {
                    return redirect()->away($result['invoice_url']);
                }
            }
        }

        $packages = SubscriptionPackageService::all();
        $channelKeys = array_keys($this->gateway->channels());

        $data = $request->validate([
            'package' => ['required', 'string', Rule::in(array_keys($packages))],
            'payment_channel' => ['required', 'string', Rule::in($channelKeys)],
            'proof_file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sessionPackage = (string) $request->session()->get(self::SESSION_CHECKOUT_PACKAGE, '');
        if ($sessionPackage === '' || $sessionPackage !== $data['package']) {
            return redirect()->to(panel_route('subscription'))
                ->with('error', 'Silakan pilih paket ulang, lalu unggah bukti pembayaran.');
        }

        if (PlatformPendingPayment::query()->where('tenant_id', $tenant->id)->where('status', 'pending')->exists()) {
            return redirect()->to(panel_route('subscription'))
                ->with('error', 'Masih ada pembayaran yang menunggu verifikasi.');
        }

        $pkg = $packages[$data['package']];
        do {
            $paymentId = $this->gateway->generatePaymentId();
        } while (PlatformPendingPayment::query()->where('payment_id', $paymentId)->exists());

        $proofPath = $request->file('proof_file')->store('platform-pending-payments', 'public');

        PlatformPendingPayment::query()->create([
            'payment_id' => $paymentId,
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'package' => $data['package'],
            'payment_channel' => $data['payment_channel'],
            'amount' => (int) ($pkg['price'] ?? 0),
            'proof_file' => $proofPath,
            'customer_note' => $data['customer_note'] ?? null,
            'status' => 'pending',
        ]);

        $request->session()->forget(self::SESSION_CHECKOUT_PACKAGE);

        return redirect()->to(panel_route('subscription'))
            ->with('success', "Bukti pembayaran terkirim (ID: {$paymentId}). Tim kami akan memverifikasi.");
    }

    private function verificationWhatsappUrl($user, PlatformPendingPayment $pending): ?string
    {
        $raw = trim((string) (PlatformSetting::get()->subscription_verification_whatsapp
            ?: config('mikpay.subscription_verification_whatsapp', '')
            ?: config('mikpay.whatsapp.number', '')));
        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?: '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }
        if (strlen($digits) < 10) {
            return null;
        }

        $tenant = $pending->tenant;
        $lines = [
            'Halo, saya '.($user->name ?? 'pengguna').' ('.$user->email.').',
            'Tenant: '.($tenant?->name ?? '—').' ('.($tenant?->subdomain ?? '—').')',
            '',
            'Mohon verifikasi pembayaran langganan MikPay.',
            'Payment ID: '.$pending->payment_id,
            'Paket: '.$pending->package,
            'Jumlah: Rp '.number_format((int) $pending->amount, 0, ',', '.'),
        ];

        return 'https://wa.me/'.$digits.'?text='.rawurlencode(implode("\n", $lines));
    }
}
