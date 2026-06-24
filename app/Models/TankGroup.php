<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TankGroup extends Model
{
    use HasFactory, \App\Traits\BelongsToFillingStation;

    protected $fillable = [
        'filling_station_id',
        'name',
        'description',
    ];

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class, 'group', 'name');
    }
}



