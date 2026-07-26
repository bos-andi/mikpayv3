<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    protected $fillable = [
        'business_name',
        'business_address',
        'business_phone',
        'business_email',
        'business_logo',
        'letterhead_image',
        'bank_account',
        'bank_name',
        'bank_account_name',
        'billing_tracking_start_date',
        'payment_channel_order',
        'subscription_premium_features',
        'subscription_packages_meta',
        'subscription_landing_headlines',
        'subscription_verification_whatsapp',
    ];

    protected function casts(): array
    {
        return [
            'billing_tracking_start_date' => 'date',
            'payment_channel_order' => 'array',
            'subscription_premium_features' => 'array',
            'subscription_packages_meta' => 'array',
            'subscription_landing_headlines' => 'array',
        ];
    }

    private static ?self $instance = null;

    protected static function booted(): void
    {
        static::saved(function () {
            self::$instance = null;
        });
    }

    public static function get(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $row = self::first();
        if ($row) {
            self::$instance = $row;
            return $row;
        }
        self::$instance = self::create([
            'business_name' => '',
            'business_address' => '',
            'business_phone' => '',
            'business_email' => '',
            'business_logo' => '',
            'letterhead_image' => '',
            'bank_account' => '',
            'bank_name' => '',
            'bank_account_name' => '',
        ]);
        return self::$instance;
    }
}
