<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TankDipping extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_id',
        'dipped_quantity',
        'atg_quantity',
        'variance',
        'dipping_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'dipped_quantity' => 'decimal:2',
        'atg_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
        'dipping_date' => 'date',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}


