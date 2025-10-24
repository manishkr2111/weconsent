<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRCode extends Model
{
    use HasFactory;

    protected $table = 'qrcodes'; // explicitly define table

    protected $fillable = [
        'user_id',
        'qr_data',
        'type',
        'generated_count',
        'scanned_at',
        'path'
    ];

    protected $casts = [
        'qr_data' => 'array',        // store JSON as array automatically
        'scanned_at' => 'datetime',  // cast scanned_at to Carbon instance
    ];

    /**
     * Relation to the user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
