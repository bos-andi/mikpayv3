<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Rules\Turnstile;
use App\Services\OnboardingService;
use App\Services\TurnstileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.login', [
            'hideNavbar' => true,
            'hideFooter' => true,
            'loginTenant' => $this->tenantFromHost($request->getHost()),
        ]);
    }

    protected function tenantFromHost(string $host): ?Tenant
    {
        $base = (string) config('mikpay.tenant_base_domain', 'mikpay.link');
        if ($base === '' || ! str_ends_with($host, '.'.$base)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen('.'.$base));
        if ($subdomain === '' || str_contains($subdomain, '.') || in_array($subdomain, ['www', 'tunnel', 'app', 'admin'], true)) {
            return null;
        }

        return Tenant::query()->where('subdomain', $subdomain)->first();
    }

    public function store(Request $request, OnboardingService $onboarding): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            TurnstileService::FIELD => [new Turnstile],
        ]);
        unset($credentials[TurnstileService::FIELD]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun Anda dinonaktifkan.']);
        }

        if ($user->requiresEmailVerification()) {
            return redirect()->away(central_url('/email/verifikasi'))
                ->with('error', 'Email Anda belum diverifikasi. Klik tautan di email kami atau kirim ulang di bawah.');
        }

        if ($user->isSuperAdmin()) {
            // Prefer superadmin home unless user explicitly opened another URL first.
            $intended = session()->pull('url.intended');

            if ($intended && ! str_contains($intended, '/login')) {
                return redirect()->to($intended);
            }

            return redirect()->away(central_url('/superadmin'));
        }

        if ($user->isPartner()) {
            return redirect()->away(central_url('/affiliate/dashboard'));
        }

        // Pelanggan Tunnel standalone → dashboard tunnel.
        $user->loadMissing('tenant');
        $tunnelHost = (string) config('mikpay.tunnel_domain', 'tunnel.mikpay.link');
        if ($request->getHost() === $tunnelHost || $user->tenant?->plan === 'tunnel') {
            return redirect()->intended(route('tunnel.dashboard'));
        }

        $tenant = $user->tenant;
        if ($tenant && $tenant->needsRenewal()) {
            $msg = 'Akun nonaktif atau langganan berakhir. Bayar paket di halaman ini untuk mengaktifkan panel kembali.';
            if ($tenant->subdomain) {
                return redirect()
                    ->away(tenant_panel_url($tenant, '/langganan'))
                    ->with('error', $msg);
            }

            return redirect()
                ->route('panel.subscription')
                ->with('error', $msg);
        }

        // Selalu arahkan ke subdomain tenant agar UI panel sama persis.
        if ($tenant?->subdomain) {
            $path = '/dashboard';
            if (
                ! $user->isSuperAdmin()
                && $tenant->subscriptionIsActive()
                && $onboarding->isEligible($tenant)
            ) {
                $checklist = $onboarding->checklist($tenant);
                if (! ($checklist['all_required_done'] ?? true) && ! ($checklist['dismissed'] ?? false)) {
                    $path = '/mulai';
                }
            }

            return redirect()->away(tenant_panel_url($tenant, $path));
        }

        return redirect()->intended(route('panel.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $host = $request->getHost();
        $base = (string) config('mikpay.tenant_base_domain', 'mikpay.link');
        $isTenantHost = str_ends_with($host, '.'.$base)
            && ! in_array(explode('.', $host)[0], ['www', 'tunnel'], true);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($isTenantHost) {
            return redirect()->away((parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https').'://'.$host.'/login');
        }

        return redirect()->route('login');
    }
}
