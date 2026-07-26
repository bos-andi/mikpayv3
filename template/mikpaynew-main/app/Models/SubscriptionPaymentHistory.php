<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPaymentHistory extends Model
{
    protected $table = 'subscription_payment_history';

    protected $fillable = [
        'subscription_id',
        'date',
        'package',
        'amount',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
