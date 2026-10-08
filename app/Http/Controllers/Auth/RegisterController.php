<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PartnerProgramService;
use App\Services\RegistrationService;
use App\Services\SubscriptionPackageService;
use App\Services\TurnstileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegisterController extends Controller
{
    protected const TURNSTILE_ERROR = 'Verifikasi keamanan gagal. Silakan selesaikan captcha lalu coba lagi.';

    public function create(Request $request, PartnerProgramService $partners): View
    {
        $code = $request->query('ref') ?: $request->cookie(PartnerProgramService::COOKIE);
        $referrer = is_string($code) ? $partners->findActiveByCode($code) : null;
        $discount = $partners->referralDiscountPercent();
        $examplePackage = collect(SubscriptionPackageService::all())->first();

        return view('auth.register', [
            'referralDiscount' => $discount,
            'discountExample' => $examplePackage ? [
                'name' => (string) $examplePackage['name'],
                'price' => (int) $examplePackage['price'],
                'discounted' => PartnerProgramService::applyDiscount((int) $examplePackage['price'], $discount),
            ] : null,
            'hideNavbar' => true,
            'hideFooter' => true,
            'tenantBase' => (string) config('mikpay.tenant_base_domain', 'mikpay.link'),
            'accountSuffix' => (string) config('mikpay.tunnel.account_suffix', 'tunnel.mikpay.link'),
            'referralCode' => $referrer?->code,
            'referrerName' => $referrer?->user?->name,
        ]);
    }

    public function checkSubdomain(Request $request, RegistrationService $registration): JsonResponse
    {
        $raw = Str::lower(trim((string) $request->query('subdomain', $request->input('subdomain', ''))));

        return response()->json($registration->evaluateSubdomainAvailability($raw));
    }

    public function checkReferral(Request $request, PartnerProgramService $partners): JsonResponse
    {
        $code = $partners->normalizeCode((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json(['valid' => false, 'message' => '']);
        }

        $partner = $partners->findActiveByCode($code);
        if (! $partner) {
            return response()->json([
                'valid' => false,
                'message' => 'Kode tidak ditemukan atau sudah tidak aktif. Kosongkan jika tidak punya.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'code' => $partner->code,
            'name' => $partner->user?->name ?: 'Affiliate MikPay',
            'discount' => $partners->referralDiscountPercent(),
        ]);
    }

    public function store(Request $request, RegistrationService $registration): RedirectResponse|StreamedResponse
    {
        if ($request->wantsJson() || $request->header('Accept') === 'application/x-ndjson') {
            return $this->storeStream($request, $registration);
        }

        if (! $this->passesTurnstile($request)) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([TurnstileService::FIELD => self::TURNSTILE_ERROR]);
        }

        try {
            $result = $registration->register($request->all(), static function (): void {
                // Tanpa progress UI untuk submit klasik.
            });
        } catch (ValidationException $e) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors($e->errors());
        }

        return redirect()
            ->away($result['redirect'])
            ->with('success', $result['success']);
    }

    public function storeStream(Request $request, RegistrationService $registration): StreamedResponse
    {
        $humanVerified = $this->passesTurnstile($request);

        return response()->stream(function () use ($request, $registration, $humanVerified) {
            $send = static function (array $payload): void {
                echo json_encode($payload, JSON_UNESCAPED_UNICODE)."\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            if (! $humanVerified) {
                $send([
                    'step' => 'validate',
                    'status' => 'error',
                    'message' => self::TURNSTILE_ERROR,
                    'errors' => [TurnstileService::FIELD => [self::TURNSTILE_ERROR]],
                ]);

                return;
            }

            try {
                $result = $registration->register($request->all(), static function (string $step, string $status, ?string $message = null) use ($send): void {
                    $send(array_filter([
                        'step' => $step,
                        'status' => $status,
                        'message' => $message,
                    ], static fn ($v) => $v !== null));
                });

                $send([
                    'step' => 'complete',
                    'status' => 'done',
                    'redirect' => $result['redirect'],
                    'success' => $result['success'],
                ]);
            } catch (ValidationException $e) {
                $send([
                    'step' => 'validate',
                    'status' => 'error',
                    'message' => collect($e->errors())->flatten()->first() ?: 'Validasi gagal.',
                    'errors' => $e->errors(),
                ]);
            } catch (\Throwable $e) {
                report($e);
                $send([
                    'step' => 'error',
                    'status' => 'error',
                    'message' => 'Pendaftaran gagal: '.$e->getMessage(),
                ]);
            }
        }, 200, [
            'Content-Type' => 'application/x-ndjson',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    protected function passesTurnstile(Request $request): bool
    {
        return app(TurnstileService::class)->verify(
            (string) $request->input(TurnstileService::FIELD, ''),
            $request->ip(),
        );
    }
}
