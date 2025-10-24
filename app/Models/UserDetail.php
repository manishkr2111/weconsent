<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'phone',
        'address',
        'dob',
        'gender',
        'gender_identity',
        'gender_orientation',
        'pronouns',
        'bio',
        'profile_image',
        'subscription_id',
        'stripe_customer_id',
        'stripe_price_id',
        'subscription_start_date',
        'subscription_end_date',
        'subscription_status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
