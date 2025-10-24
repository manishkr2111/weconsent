<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecentActivity extends Model
{
    use HasFactory;

    protected $table = 'recent_activities';

    // Fillable fields for mass assignment
    protected $fillable = [
        'user_id',
        'action',
        'details',
        'type',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
