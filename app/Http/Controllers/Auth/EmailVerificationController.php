<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\WelcomeTenantNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->requiresEmailVerification()) {
            return redirect()->away(self::homeFor($user));
        }

        return view('auth.verify-email', [
            'hideNavbar' => true,
            'hideFooter' => true,
            'user' => $user,
        ]);
    }

    public function verify(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::query()->find($id);
        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect()->away(central_url('/login'))
                ->with('error', 'Tautan verifikasi tidak valid. Login lalu minta tautan baru.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
            self::activateAfterVerification($user);
        }

        $current = $request->user();
        if ($current && (int) $current->id === (int) $user->id) {
            return redirect()->away(self::homeFor($user))
                ->with('success', 'Email berhasil diverifikasi. Akun Anda sudah aktif.');
        }

        return redirect()->away(central_url('/login'))
            ->with('success', 'Email '.$user->email.' berhasil diverifikasi. Silakan masuk.');
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->requiresEmailVerification()) {
            return redirect()->away(self::homeFor($user));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Tautan verifikasi baru sudah dikirim ke '.$user->email.'.');
    }

    public function changeEmail(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->requiresEmailVerification()) {
            return redirect()->away(self::homeFor($user));
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ], [
            'email.unique' => 'Email ini sudah dipakai akun lain.',
            'current_password.current_password' => 'Password salah.',
        ], [
            'email' => 'Email baru',
            'current_password' => 'Password',
        ]);

        if (User::isAllowedSuperAdminEmail($data['email'])) {
            return back()->withErrors(['email' => 'Email ini tidak bisa dipakai.']);
        }

        $user->forceFill(['email' => strtolower(trim($data['email'])), 'email_verified_at' => null])->save();
        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Email diganti. Tautan verifikasi dikirim ke '.$user->email.'.');
    }

    /**
     * Aktifkan tenant milik user setelah email terverifikasi: buka kunci panel/VPN dan mulai trial dari sekarang.
     */
    public static function activateAfterVerification(User $user): void
    {
        $tenant = $user->tenant;
        if (! $tenant || ! $tenant->awaitingEmailVerification()) {
            return;
        }

        $meta = is_array($tenant->meta) ? $tenant->meta : [];
        unset($meta['email_verification_pending']);
        $tenant->meta = $meta;

        if (($tenant->plan ?? '') === 'trial' && ! $tenant->metaGet('subscription_ends_at')) {
            $tenant->metaSet('trial_ends_at', wib_now()->addDays((int) config('mikpay.trial_days', 3))->toIso8601String());
        }
        $tenant->save();

        Log::info('tenant.email_verified', ['tenant_id' => $tenant->id, 'user_id' => $user->id]);

        if (($tenant->plan ?? '') !== 'tunnel') {
            try {
                $user->notify(new WelcomeTenantNotification($tenant));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    public static function homeFor(User $user): string
    {
        if ($user->isSuperAdmin()) {
            return central_url('/superadmin');
        }
        if ($user->isPartner()) {
            return central_url('/affiliate/dashboard');
        }

        $tenant = $user->tenant;
        if ($tenant?->plan === 'tunnel') {
            return route('tunnel.dashboard');
        }

        return $tenant?->subdomain ? tenant_panel_url($tenant, '/mulai') : central_url('/dashboard');
    }
}
