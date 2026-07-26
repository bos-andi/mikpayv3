<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPeriodPromise extends Model
{
    protected $table = 'billing_period_promises';

    protected $fillable = [
        'customer_name',
        'router_session',
        'period',
        'promised_pay_date',
        'promised_pay_note',
    ];

    protected function casts(): array
    {
        return [
            'promised_pay_date' => 'date',
        ];
    }
}
