<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'si_unit',
        'quantity',
        'cost_price',
        'retail_price',
        'dealer_price',
        'bulk_price',
        're_order_level',
        'iot_product',
        'created_by',
        'last_modified_by',
    ];

    protected $appends = [
        'created_by_name',
        'last_modified_by_name',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'retail_price' => 'decimal:2',
        'dealer_price' => 'decimal:2',
        'bulk_price' => 'decimal:2',
        're_order_level' => 'decimal:2',
    ];

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function priceAdjustments(): HasMany
    {
        return $this->hasMany(PriceAdjustment::class);
    }

    public function inventoryReconciliations(): HasMany
    {
        return $this->hasMany(InventoryReconciliation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastModifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    public function getCreatedByNameAttribute(): ?string
    {
        return optional($this->creator)->name;
    }

    public function getLastModifiedByNameAttribute(): ?string
    {
        return optional($this->lastModifier)->name;
    }
}


