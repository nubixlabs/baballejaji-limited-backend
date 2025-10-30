<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'partNumber',
        'category',
        'brand',
        'vehicleType',
        'costPrice',
        'price',
        'stock',
        'minStock',
        'maxStock',
        'supplier_id',
        'description',
    ];

    protected $appends = ['supplier']; // expose supplier name to frontend

    /**
     * Relationships
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Accessor: Return supplier name as 'supplier' field in JSON
     */
    public function getSupplierAttribute()
    {
        return $this->supplier()->exists() ? $this->supplier->name : null;
    }
}
