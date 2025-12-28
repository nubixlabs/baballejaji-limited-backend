<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_tank_id',
        'to_tank_id',
        'quantity',
        'transfer_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'transfer_date' => 'date',
    ];

    public function fromTank(): BelongsTo
    {
        return $this->belongsTo(Tank::class, 'from_tank_id');
    }

    public function toTank(): BelongsTo
    {
        return $this->belongsTo(Tank::class, 'to_tank_id');
    }
}



