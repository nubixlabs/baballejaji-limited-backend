<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    use HasFactory;

    protected $fillable = [
        'truck_type_id',
        'engine_number',
        'truck_number',
        'driver_name',
        'driver_phone',
        'driver_address',
        'status'
    ];

    public function type()
    {
        return $this->belongsTo(TruckType::class, 'truck_type_id');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
