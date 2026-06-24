<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory, \App\Traits\BelongsToFillingStation;

    protected $fillable = [
        'filling_station_id',
        'order_id',
        'part_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}


