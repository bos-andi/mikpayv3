<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'hideNavbar' => true,
            'hideFooter' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

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

        if ($user->isSuperAdmin()) {
            // Prefer superadmin home unless user explicitly opened another URL first.
            $intended = session()->pull('url.intended');

            if ($intended && ! str_contains($intended, '/login')) {
                return redirect()->to($intended);
            }

            return redirect()->route('superadmin.dashboard');
        }

        // Pelanggan Tunnel standalone → dashboard tunnel.
        $user->loadMissing('tenant');
        $tunnelHost = (string) config('mikpay.tunnel_domain', 'tunnel.mikpay.link');
        if ($request->getHost() === $tunnelHost || $user->tenant?->plan === 'tunnel') {
            return redirect()->intended(route('tunnel.dashboard'));
        }

        $tenant = $user->tenant;
        if (
            $tenant
            && $tenant->subscriptionEndsAt() !== null
            && ! $tenant->subscriptionIsActive()
        ) {
            if ($tenant->subdomain) {
                return redirect()
                    ->away(tenant_panel_url($tenant, '/langganan'))
                    ->with('error', 'Trial/langganan sudah berakhir. Perpanjang di halaman ini untuk membuka panel kembali.');
            }

            return redirect()
                ->route('panel.subscription')
                ->with('error', 'Trial/langganan sudah berakhir. Perpanjang di halaman ini untuk membuka panel kembali.');
        }

        // Selalu arahkan ke subdomain tenant agar UI panel sama persis.
        if ($tenant?->subdomain) {
            return redirect()->away(tenant_panel_url($tenant, '/dashboard'));
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
