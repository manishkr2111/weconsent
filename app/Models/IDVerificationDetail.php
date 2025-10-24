<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IDVerificationDetail extends Model
{
    use HasFactory;

    protected $table = 'id_verification_details';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'applicant_id',
        'first_name',
        'last_name',
        'dob',
        'gender',
        'country',
        'email',
        'phone',
        'review_status',
        'review_answer',
        'create_date',
        'review_date',
        'id_docs',
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'create_date' => 'datetime',
        'review_date' => 'datetime',
        'id_docs' => 'array',
    ];

    /**
     * Relationship: ID Verification belongs to a User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper: Check if verification is approved
     */
    public function isApproved(): bool
    {
        return strtolower($this->review_status) === 'completed' &&
               strtolower($this->review_answer) === 'green';
    }

    /**
     * Helper: Check if verification is pending
     */
    public function isPending(): bool
    {
        return strtolower($this->review_status) === 'pending';
    }

    /**
     * Helper: Check if verification is rejected
     */
    public function isRejected(): bool
    {
        return strtolower($this->review_status) === 'rejected';
    }
}
