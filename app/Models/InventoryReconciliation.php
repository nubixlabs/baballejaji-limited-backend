<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'system_quantity',
        'physical_quantity',
        'variance',
        'reconciliation_date',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'physical_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
        'reconciliation_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}



