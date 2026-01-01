<?php

namespace App\Traits;

use App\Models\FillingStation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait BelongsToFillingStation
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToFillingStation(): void
    {
        // Add global scope to filter by filling station from header
        static::addGlobalScope('filling_station', function (Builder $builder) {
            $fillingStationId = request()->header('X-Filling-Station-Id');
            
            // Only apply if header is present and valid
            if ($fillingStationId && is_numeric($fillingStationId)) {
                
                // If the model is shared (e.g., users can see across stations if authorized), 
                // we might need more complex logic, but for isolation:
                $builder->where('filling_station_id', $fillingStationId);
            }
        });

        // Auto-assign filling station on creation
        static::creating(function ($model) {
            if (!$model->filling_station_id) {
                $fillingStationId = request()->header('X-Filling-Station-Id');
                if ($fillingStationId && is_numeric($fillingStationId)) {
                    $model->filling_station_id = $fillingStationId;
                }
            }
        });
    }

    /**
     * Relationship to FillingStation
     */
    public function fillingStation()
    {
        return $this->belongsTo(FillingStation::class);
    }
}
