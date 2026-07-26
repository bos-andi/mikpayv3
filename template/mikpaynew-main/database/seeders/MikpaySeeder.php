<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use App\Models\FonnteSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MikpaySeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('username', 'mikpay')->exists()) {
            return;
        }

        $user = User::create([
            'name' => 'MIKPAY Admin',
            'username' => 'mikpay',
            'email' => null,
            'password' => Hash::make('1234'),
            'role' => 'admin',
            'is_super_admin' => false,
            'status' => 'active',
        ]);

        if (! Subscription::first()) {
            Subscription::create([
                'status' => 'trial',
                'package' => 'trial',
                'start_date' => now(),
                'end_date' => now()->addDays(7),
            ]);
        }

        if (! BusinessSetting::first()) {
            BusinessSetting::create([
                'business_name' => '',
                'business_address' => '',
                'business_phone' => '',
                'business_email' => '',
                'business_logo' => '',
                'bank_account' => '',
                'bank_name' => '',
                'bank_account_name' => '',
            ]);
        }

        if (! FonnteSetting::where('user_id', $user->id)->exists()) {
            FonnteSetting::create(array_merge(
                FonnteSetting::defaultTemplatePayload(),
                ['user_id' => $user->id]
            ));
        }
    }
}
