<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsentRequest extends Model
{
    use HasFactory, SoftDeletes;

    // Define the table name (optional, Laravel will automatically use 'consent_requests' based on the model name)
    protected $table = 'consent_requests';

    // Define the primary key (optional, since the default is 'id', this is not necessary)
    protected $primaryKey = 'id';

    // Define the columns that are mass assignable
    protected $fillable = [
        'created_by',
        'sent_to',
        'consent_type',
        'date_type',
        'intimacy_type',
        'other_type_description',
        'status',
        'sent_otp',
        'accept_otp',
        'sent_otp_verified_at',
        'accept_otp_verified_at',
        'accept_or_rejected_at',
        'event_date',  
        'event_duration',
        'location',
        'intimacy_code'
    ];

    // Optionally, define hidden attributes (such as sensitive data you don't want in the array)
    protected $hidden = [
        'sent_otp', // For security, OTP should be hidden when returning as array or JSON
        'accept_otp',
        //'intimacy_code'
    ];

    // Define any dates (Laravel will automatically cast these as Carbon instances)
    protected $dates = [
        'sent_otp_verified_at',
        'accept_otp_verified_at',
        'deleted_at',  // Soft delete timestamp
    ];
    
    protected $casts = [
        'location' => 'array',
    ];
    // Relationships

    // The user who created the consent request (belongs to a User model)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // The user to whom the consent request is sent (belongs to a User model)
    public function sentTo()
    {
        return $this->belongsTo(User::class, 'sent_to');
    }

    // Optional: Add additional methods for business logic if needed

    /**
     * Check if the OTP is valid
     * 
     * @param string $otp
     * @return bool
     */
    public function isOtpValid($otp)
    {
        return $this->sent_otp === $otp;
    }

    /**
     * Mark the OTP as verified
     */
    public function verifySentOtp()
    {
        $this->sent_otp_verified_at = now();
        $this->save();
    }

    /**
     * Mark the acceptance OTP as verified
     */
    public function verifyAcceptOtp()
    {
        $this->accept_otp_verified_at = now();
        $this->save();
    }

    /**
     * Scope for filtering requests by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering soft deleted requests
     */
    public function scopeWithTrashedRequests($query)
    {
        return $query->withTrashed();
    }

    // You can add more custom scopes or methods for additional filtering, or actions
}
