<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\TurnstileService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password', [
            'hideNavbar' => true,
            'hideFooter' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            TurnstileService::FIELD => [new Turnstile],
        ]);

        $status = Password::sendResetLink(
            ['email' => Str::lower(trim((string) $request->input('email')))],
        );

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Tunggu sebentar sebelum meminta tautan reset lagi.']);
        }

        // Pesan sama untuk email terdaftar/tidak agar daftar akun tidak bisa ditebak.
        return back()->with('status', 'Jika email terdaftar, tautan reset password sudah dikirim. Cek kotak masuk atau folder spam Anda.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'hideNavbar' => true,
            'hideFooter' => true,
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => match ($status) {
                    Password::INVALID_TOKEN => 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
                    Password::INVALID_USER => 'Email tidak cocok dengan tautan reset.',
                    default => 'Reset password gagal. Silakan coba lagi.',
                }]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil diubah. Silakan masuk dengan password baru.');
    }
}
