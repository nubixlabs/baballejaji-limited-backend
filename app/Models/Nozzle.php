<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Nozzle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tank_id',
        'description',
        'status',
        'reading',
        'type',
        'dispenser_type',
        'is_online',
        'created_by',
        'last_modified_by',
        'modified_at',
    ];

    protected $casts = [
        'reading' => 'decimal:2',
        'is_online' => 'boolean',
        'modified_at' => 'datetime',
    ];

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }
}
