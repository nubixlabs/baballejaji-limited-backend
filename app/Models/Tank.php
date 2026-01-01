<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tank extends Model
{
    use HasFactory, \App\Traits\BelongsToFillingStation;

    protected $fillable = [
        'filling_station_id',
        'name',
        'product_id',
        'capacity',
        'content',
        'level',
        'atg_status',
        'group',
        'fillup_id',
    ];

    protected $casts = [
        'capacity' => 'decimal:2',
        'content' => 'decimal:2',
        'level' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function dippings(): HasMany
    {
        return $this->hasMany(TankDipping::class);
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(TankTransfer::class, 'from_tank_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(TankTransfer::class, 'to_tank_id');
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }
}

