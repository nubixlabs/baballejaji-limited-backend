<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'status',
        'notes',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
