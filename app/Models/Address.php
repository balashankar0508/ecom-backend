<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'name',       // Changed from first_name/last_name
        'phone',
        'line1',      // Changed from address_line_1
        'city',
        'state',
        'postal_code',
        'type',       // New field for 'billing' or 'shipping'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}