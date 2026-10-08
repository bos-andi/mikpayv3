<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\PartnerProgramService;
use App\Services\SubscriptionPackageService;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PartnerPublicController extends Controller
{
    public function __construct(private PartnerProgramService $partners) {}

    public function landing(): View
    {
        return view('partner.landing', [
            'settings' => $this->partners->settings(),
            'packages' => SubscriptionPackageService::all(),
            'trialDays' => (int) config('mikpay.trial_days', 3),
            'navSolid' => true,
            'navCtaUrl' => auth()->user()?->isPartner() ? route('partner.dashboard') : route('partner.register'),
            'navCtaLabel' => auth()->user()?->isPartner() ? 'Panel Affiliate' : 'Daftar Affiliate',
            'navLinks' => [
                [route('home'), 'Produk MikPay'],
                ['#cara-kerja-affiliate', 'Cara Kerja'],
                ['#kalkulator', 'Kalkulator', 'BARU'],
                ['#faq-affiliate', 'FAQ'],
            ],
        ]);
    }

    public function shortLink(string $code): RedirectResponse
    {
        return redirect('/?ref='.urlencode(PartnerProgramService::normalizeCode($code)));
    }

    public function create(): View
    {
        return view('partner.register', [
            'hideNavbar' => true,
            'hideFooter' => true,
            'settings' => $this->partners->settings(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'city' => ['required', 'string', 'max:100'],
            'promotion_plan' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'agree' => ['accepted'],
            TurnstileService::FIELD => [new Turnstile],
        ], [
            'email.unique' => 'Email sudah terdaftar. Gunakan email lain atau masuk.',
            'phone.regex' => 'Nomor WhatsApp tidak valid.',
            'agree.accepted' => 'Centang persetujuan syarat Program Affiliate.',
        ], [
            'name' => 'Nama lengkap',
            'phone' => 'WhatsApp',
            'city' => 'Kota',
            'promotion_plan' => 'Rencana promosi',
        ]);

        if (User::isAllowedSuperAdminEmail($data['email'])) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'Email ini tidak bisa dipakai untuk mendaftar affiliate.']);
        }

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => User::ROLE_PARTNER,
                'tenant_id' => null,
                'is_active' => true,
            ]);

            Partner::query()->create([
                'user_id' => $user->id,
                'status' => Partner::STATUS_PENDING,
                'phone' => $data['phone'],
                'city' => $data['city'],
                'promotion_plan' => $data['promotion_plan'] ?? null,
            ]);

            return $user;
        });

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice')
            ->with('success', "Pendaftaran affiliate berhasil! Klik tautan verifikasi yang kami kirim ke {$user->email}. Setelah itu tim MikPay akan meninjau akun Anda.");
    }
}
