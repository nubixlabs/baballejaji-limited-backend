<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'old_cost_price',
        'new_cost_price',
        'old_retail_price',
        'new_retail_price',
        'old_dealer_price',
        'new_dealer_price',
        'old_bulk_price',
        'new_bulk_price',
        'adjustment_date',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'old_cost_price' => 'decimal:2',
        'new_cost_price' => 'decimal:2',
        'old_retail_price' => 'decimal:2',
        'new_retail_price' => 'decimal:2',
        'old_dealer_price' => 'decimal:2',
        'new_dealer_price' => 'decimal:2',
        'old_bulk_price' => 'decimal:2',
        'new_bulk_price' => 'decimal:2',
        'adjustment_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

