<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSalesSummary extends Model
{
    protected $fillable = [
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
