<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'landing_content',
        'subscription_packages_meta',
        'subscription_landing_headlines',
        'payment_channel_order',
        'subscription_verification_whatsapp',
    ];

    protected function casts(): array
    {
        return [
            'landing_content' => 'array',
            'subscription_packages_meta' => 'array',
            'subscription_landing_headlines' => 'array',
            'payment_channel_order' => 'array',
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

        $row = self::query()->first();
        if ($row) {
            self::$instance = $row;

            return $row;
        }

        self::$instance = self::query()->create([]);

        return self::$instance;
    }
}
