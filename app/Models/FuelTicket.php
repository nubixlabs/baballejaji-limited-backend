<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FuelTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'fuel_ticket_number',
        'date',
        'product_id',
        'rate',
        'quantity',
        'trip_allowance',
        'total_amount',
        'truck_capacity',
        'truck_number',
        'loading_point',
        'destination',
        'driver_name',
        'driver_phone',
        'truck_provider',
        'attachment_path',
        'details',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'rate' => 'decimal:2',
        'quantity' => 'decimal:2',
        'trip_allowance' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
