<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSalesSummary extends Model
{
    use \App\Traits\BelongsToFillingStation;

    protected $fillable = [
        'filling_station_id',
        'shift_id',
        'product_id',
        'cost_price',
        'pump_price',
        'shift_vol',
        'shift_amount',
        'bulk_sales',
        'retail_sales',
        'total_revenue',
        'date',
    ];
}
