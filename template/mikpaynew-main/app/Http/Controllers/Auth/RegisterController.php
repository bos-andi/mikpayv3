<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SubscriptionPackageService;
use App\Support\SuperAdminCredentials;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function showRegistrationForm()
    {
        if (Auth::check()) {
            return redirect()->route('sessions');
        }

        return view('auth.register', [
            'subscriptionPackages' => SubscriptionPackageService::all(),
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:4', 'max:20', 'unique:users,username', 'regex:/^[A-Za-z0-9._-]+$/'],
            'business_name' => ['required', 'string', 'max:255'],
            'business_address' => ['required', 'string', 'max:500'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'subscription_choice' => ['required', 'in:trial,paket'],
            'package_code' => ['nullable', 'required_if:subscription_choice,paket', Rule::in(SubscriptionPackageService::paidPackageKeys())],
            'password' => ['required', 'confirmed', Password::min(8)],
            'agree_terms' => ['accepted'],
        ], [
            'username.regex' => 'Username hanya huruf, angka, titik, underscore, dan strip (tanpa spasi).',
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid. Gunakan format 08xx / 62xx / +62xx.',
            'agree_terms.accepted' => 'Anda wajib menyetujui syarat & kebijakan privasi.',
        ]);

        $subscriptionStart = Carbon::today();
        $subscriptionEnd = null;
        $subscriptionStatus = 'pending_payment';
        $subscriptionPackage = $validated['package_code'] ?? null;

        if ($validated['subscription_choice'] === 'trial') {
            $subscriptionStatus = 'trial';
            $subscriptionPackage = 'trial_5_days';
            $subscriptionEnd = $subscriptionStart->copy()->addDays(5);
        }

        $user = User::create([
            'name' => $validated['name'],
            'username' => strtolower($validated['username']),
            'business_name' => $validated['business_name'],
            'business_address' => $validated['business_address'],
            'whatsapp' => $validated['whatsapp'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'is_super_admin' => false,
            'status' => 'active',
            'subscription_package' => $subscriptionPackage,
            'subscription_status' => $subscriptionStatus,
            'subscription_start' => $subscriptionStart,
            'subscription_end' => $subscriptionEnd,
        ]);

        Auth::login($user);
        $request->session()->forget(SuperAdminCredentials::sessionKey());
        $request->session()->regenerate();

        $redirectRoute = $validated['subscription_choice'] === 'trial' ? 'sessions' : 'langganan';

        return redirect()->route($redirectRoute)->with(
            'success',
            $validated['subscription_choice'] === 'trial'
                ? 'Akun berhasil dibuat. Trial 5 hari sudah aktif, silakan lanjut setup MikroTik.'
                : 'Akun berhasil dibuat. Silakan selesaikan pembayaran paket di halaman Langganan.'
        );
    }
}
