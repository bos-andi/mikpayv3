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

        return redirect()->intended(route('panel.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
