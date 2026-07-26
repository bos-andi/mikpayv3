<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\SuperAdminCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SuperAdminLoginController extends Controller
{
    public function showLoginForm()
    {
        if ($this->superAdminSessionActive()) {
            return redirect()->route('superadmin.dashboard');
        }

        if (Auth::check()) {
            return redirect()->route('sessions')
                ->with('error', 'Gunakan halaman login user untuk akun ini.');
        }

        return view('auth.superadmin-login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'user' => 'required|string',
            'pass' => 'required|string',
        ], [], [
            'user' => 'username',
            'pass' => 'password',
        ]);

        $login = $request->input('user');
        $password = $request->input('pass');

        if (! SuperAdminCredentials::validateLogin($login, $password)) {
            throw ValidationException::withMessages([
                'user' => [__('auth.failed')],
            ]);
        }

        Auth::logout();

        $request->session()->regenerate();
        $request->session()->put(SuperAdminCredentials::sessionKey(), true);

        return redirect()->intended(route('superadmin.dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget(SuperAdminCredentials::sessionKey());
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login');
    }

    private function superAdminSessionActive(): bool
    {
        return (bool) session()->get(SuperAdminCredentials::sessionKey());
    }
}
