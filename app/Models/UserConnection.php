<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserConnection extends Model
{
    use SoftDeletes;

    protected $table = 'user_connection';

    protected $fillable = [
        'sender_id', 'receiver_id', 'status','consent_id'
    ];

    // Relationship to sender user
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Relationship to receiver user
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Scope to get only the accepted connections
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    // Scope to get pending connections
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Scope to get rejected connections
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Scope to get blocked connections
    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }
}
