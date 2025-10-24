<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subscription_id',
        'customer_id',
        'price_id',
        'status',
        'start_date',
        'end_date',
        'payment_method_id',
        'card_brand',
        'card_last4',
        'card_exp_month',
        'card_exp_year',
        'billing_email',
        'billing_name',
        'billing_phone',
        'billing_address',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
