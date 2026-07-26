<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingCustomer extends Model
{
    protected $fillable = [
        'customer_name',
        'due_day',
        'display_name',
        'phone',
        'monthly_fee',
        'notes',
        'router_session',
        'address',
        'zone',
        'package_label',
        'email',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'due_day' => 'integer',
            'monthly_fee' => 'integer',
            'started_at' => 'date',
        ];
    }

    public function payments()
    {
        return $this->hasMany(BillingPayment::class, 'customer_name', 'customer_name');
    }
}
