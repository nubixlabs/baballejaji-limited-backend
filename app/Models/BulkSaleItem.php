<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkSaleItem extends Model
{
    use HasFactory, \App\Traits\BelongsToFillingStation;

    protected $fillable = [
        'filling_station_id',
        'bulk_sale_id',
        'product_id',
        'quantity',
        'price',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function bulkSale(): BelongsTo
    {
        return $this->belongsTo(BulkSale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}



