<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingPayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'customer_name',
        'display_name',
        'phone',
        'amount',
        'remaining_amount',
        'period',
        'payment_date',
        'payment_method',
        'notes',
        'status',
        'router_session',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'remaining_amount' => 'integer',
            'payment_date' => 'datetime',
        ];
    }

    /** Relasi ke CashTransaction (jika pembayaran ini tercatat ke Kas) */
    public function cashTransaction()
    {
        return $this->hasOne(CashTransaction::class, 'source_id')->where('source_type', 'ppp_payment');
    }
}
