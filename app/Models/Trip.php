<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_number',
        'truck_id',
        'from_location',
        'destination',
        'status',
        'approval_status',
    ];

    public function truck()
    {
        return $this->belongsTo(Truck::class);
    }

    public function ledgers()
    {
        return $this->hasMany(TripLedger::class);
    }
}
