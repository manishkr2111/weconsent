<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class websiteSetting extends Model
{
    use HasFactory;

    // Define the table associated with the model
    protected $table = 'settings';  // Ensure your table is named 'settings'

    // Allow mass assignment on these columns
    protected $fillable = [
        'logo', 
        'content', 
        'title', 
        'meta_description', 
        'emails', 
        'contact_number', 
        'footer_text'
    ];

    // If you want to use timestamps (created_at, updated_at)
    public $timestamps = true;

    // If you don't want to use timestamps, set this to false
    // public $timestamps = false;

    // Optionally, if you want to define a custom primary key
    // protected $primaryKey = 'id';  // By default, Laravel assumes the primary key is 'id'

    // If you are using a non-integer primary key, specify the type:
    // protected $keyType = 'string'; 
}
