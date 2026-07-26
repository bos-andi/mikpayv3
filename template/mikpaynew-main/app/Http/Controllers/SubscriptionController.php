<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\PendingPayment;
use App\Support\SubscriptionGate;
use App\Support\TenantRouterScope;
use App\Services\ManualPaymentGatewayService;
use App\Services\SubscriptionPackageService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public const SESSION_CHECKOUT_PACKAGE = 'langganan_checkout_package';

    public function __construct(private readonly ManualPaymentGatewayService $gateway)
    {
    }

    private function subscriptionVerificationWhatsappRecipient(): ?string
    {
        $fromDb = trim((string) (BusinessSetting::get()->subscription_verification_whatsapp ?? ''));
        if ($fromDb !== '') {
            return $fromDb;
        }

        return trim((string) config('mikpay.subscription_verification_whatsapp', '')) ?: null;
    }

    private function normalizeWhatsappDigits(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '62')) {
            return $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return strlen($digits) >= 10 ? $digits : null;
    }

    private function subscriptionVerificationWhatsappUrl($user, PendingPayment $pending): ?string
    {
        $raw = $this->subscriptionVerificationWhatsappRecipient();
        if ($raw === null || $raw === '') {
            return null;
        }
        $digits = $this->normalizeWhatsappDigits($raw);
        if ($digits === null) {
            return null;
        }

        $lines = [
            'Halo, saya '.($user->name ?? 'pelanggan').' (username: '.($user->username ?? '—').').',
            '',
            'Mohon bantu verifikasi pembayaran langganan MikPay.',
            'Payment ID: '.$pending->payment_id,
            'Paket: '.$pending->package,
            'Jumlah: Rp '.number_format((int) $pending->amount, 0, ',', '.'),
            '',
            'Terima kasih.',
        ];
        $text = implode("\n", $lines);

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
    }

    /**
     * @return array{routers: \Illuminate\Support\Collection, currentRouter: ?\App\Models\MikpayRouter, identity: string, clock: array<string, string>}
     */
    private function layoutContext(Request $request): array
    {
        $user = $request->user();
        $routers = TenantRouterScope::forUser((int) $user->id)->where('status', 'enable')->orderBy('session_name')->get();
        $sessionRouter = $request->session()->get('selected_router');

        $currentRouter = $sessionRouter
            ? $routers->firstWhere('session_name', $sessionRouter)
            : ($routers->first() ?: null);

        $identity = $currentRouter?->router_name ?: ($currentRouter?->session_name ?: 'MIKPAY');
        $clock = ['time' => date('H:i:s'), 'date' => date('d M Y'), 'time-zone-name' => date_default_timezone_get()];

        return [
            'routers' => $routers,
            'currentRouter' => $currentRouter,
            'identity' => $identity,
            'clock' => $clock,
        ];
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $ctx = $this->layoutContext($request);

        // Prioritaskan subscription milik user login.
        $subscription = (object) [
            'package' => $user?->subscription_package,
            'end_date' => $user?->subscription_end,
            'status' => $user?->subscription_status,
        ];
        $packages = SubscriptionPackageService::all();
        $paymentChannels = $this->gateway->channels();

        $endDate = null;
        if (! empty($subscription->end_date)) {
            try {
                $endDate = $subscription->end_date instanceof \Illuminate\Support\Carbon
                    ? $subscription->end_date
                    : Carbon::parse($subscription->end_date);
            } catch (\Throwable $e) {
                $endDate = null;
            }
        }

        $isActive = SubscriptionGate::allowsAppAccess($user);
        $remainingDays = null;
        if ($endDate) {
            $remainingDays = max(0, (int) now()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false));
        }
        $subscription_until = $endDate ? $endDate->format('d M Y') : null;

        $pendingPayments = PendingPayment::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
        $hasPending = $pendingPayments->where('status', 'pending')->isNotEmpty();
        $pendingLatest = $hasPending ? $pendingPayments->firstWhere('status', 'pending') : null;
        $subscriptionVerifyWaUrl = ($hasPending && $pendingLatest)
            ? $this->subscriptionVerificationWhatsappUrl($user, $pendingLatest)
            : null;

        // Riwayat approval global lama tidak dipakai untuk mode per-user.
        $paymentHistory = collect();

        return view('subscription.index', array_merge($ctx, [
            'subscription' => $subscription,
            'packages' => $packages,
            'paymentChannels' => $paymentChannels,
            'isActive' => $isActive,
            'remainingDays' => $remainingDays,
            'subscription_until' => $subscription_until,
            'hasPending' => $hasPending,
            'pendingPayments' => $pendingPayments,
            'paymentHistory' => $paymentHistory,
            'subscriptionVerifyWaUrl' => $subscriptionVerifyWaUrl,
        ]));
    }

    public function selectPackage(Request $request)
    {
        $user = $request->user();
        $packages = SubscriptionPackageService::all();
        $packageKeys = array_keys($packages);

        $validated = $request->validate([
            'package' => ['required', 'string', Rule::in($packageKeys)],
        ]);

        if (PendingPayment::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            return redirect()
                ->route('langganan')
                ->withErrors(['package' => 'Anda masih memiliki pembayaran yang menunggu konfirmasi admin.']);
        }

        $request->session()->put(self::SESSION_CHECKOUT_PACKAGE, $validated['package']);

        return redirect()->route('langganan.pembayaran');
    }

    public function cancelCheckout(Request $request)
    {
        $request->session()->forget(self::SESSION_CHECKOUT_PACKAGE);

        return redirect()->route('langganan')->with('success', 'Pemilihan paket dibatalkan. Silakan pilih paket kembali jika perlu.');
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $packageKey = (string) $request->session()->get(self::SESSION_CHECKOUT_PACKAGE, '');
        $packages = SubscriptionPackageService::all();

        if ($packageKey === '' || ! isset($packages[$packageKey])) {
            return redirect()
                ->route('langganan')
                ->with('error', 'Pilih paket terlebih dahulu di halaman Langganan.');
        }

        if (PendingPayment::where('user_id', $user->id)->where('status', 'pending')->exists()) {
            $request->session()->forget(self::SESSION_CHECKOUT_PACKAGE);

            return redirect()
                ->route('langganan')
                ->withErrors(['package' => 'Anda masih memiliki pembayaran yang menunggu konfirmasi admin.']);
        }

        $ctx = $this->layoutContext($request);

        return view('subscription.checkout', array_merge($ctx, [
            'selectedPackageKey' => $packageKey,
            'selectedPackage' => $packages[$packageKey],
            'paymentChannels' => $this->gateway->channels(),
        ]));
    }

    public function requestPayment(Request $request)
    {
        $user = $request->user();
        $packages = SubscriptionPackageService::all();
        $packageKeys = array_keys($packages);
        $channelKeys = array_keys($this->gateway->channels());

        $validated = $request->validate([
            'package' => ['required', 'string', Rule::in($packageKeys)],
            'payment_channel' => ['required', 'string', Rule::in($channelKeys)],
            'proof_file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,pdf'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sessionPackage = (string) $request->session()->get(self::SESSION_CHECKOUT_PACKAGE, '');
        if ($sessionPackage === '' || $sessionPackage !== $validated['package']) {
            return redirect()
                ->route('langganan')
                ->with('error', 'Silakan pilih paket, lalu unggah bukti di halaman pembayaran.');
        }

        $existingPending = PendingPayment::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
        if ($existingPending) {
            return redirect()
                ->route('langganan')
                ->withErrors(['package' => 'Anda masih memiliki pembayaran yang menunggu konfirmasi admin.']);
        }

        $packageKey = $validated['package'];
        $pkg = $packages[$packageKey];
        $amount = (int) $pkg['price'];

        do {
            $paymentId = $this->gateway->generatePaymentId();
        } while (PendingPayment::where('payment_id', $paymentId)->exists());

        $proofPath = $request->file('proof_file')->store('pending-payments', 'public');

        PendingPayment::create([
            'payment_id' => $paymentId,
            'user_id' => $user->id,
            'package' => $packageKey,
            'payment_channel' => $validated['payment_channel'],
            'amount' => $amount,
            'proof_file' => $proofPath,
            'customer_note' => $validated['customer_note'] ?? null,
            'status' => 'pending',
        ]);

        $request->session()->forget(self::SESSION_CHECKOUT_PACKAGE);

        return redirect()
            ->route('langganan')
            ->with('success', "Pembayaran berhasil dikirim! ID: {$paymentId}. Tim kami akan memverifikasi bukti Anda.");
    }
}

