<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'date',
        'description',
        'litres',
        'price_per_litre',
        'debit',
        'credit',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
