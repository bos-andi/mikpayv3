<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SubscriptionGate;
use App\Support\SuperAdminCredentials;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
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

        $user = User::where('username', $login)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => [__('auth.failed')],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'user' => [__('Account is inactive.')],
            ]);
        }

        if (! \Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'user' => [__('auth.failed')],
            ]);
        }

        Auth::login($user, (bool) $request->boolean('remember'));

        $request->session()->forget(SuperAdminCredentials::sessionKey());
        $request->session()->regenerate();

        if (! SubscriptionGate::allowsAppAccess($user)) {
            return redirect()
                ->route('langganan')
                ->with('error', 'Langganan tidak aktif atau sudah berakhir. Silakan perpanjang di halaman ini.');
        }

        return redirect()->intended(route('sessions'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
