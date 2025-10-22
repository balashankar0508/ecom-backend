<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier',
        'status',
        'shipped_at',
        'estimated_delivery',
    ];

    protected $casts = ['shipped_at' => 'datetime', 'estimated_delivery' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}