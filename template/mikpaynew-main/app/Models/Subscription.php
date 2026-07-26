<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'status',
        'package',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function paymentHistory()
    {
        return $this->hasMany(SubscriptionPaymentHistory::class, 'subscription_id');
    }

    public static function getGlobal(): self
    {
        $sub = self::first();
        if ($sub) {
            return $sub;
        }
        return self::create([
            'status' => 'trial',
            'package' => 'trial',
            'start_date' => now(),
            'end_date' => now()->addDays(7),
        ]);
    }
}
