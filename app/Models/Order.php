<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customerName',
        'customerEmail',
        'customerPhone',
        'deliveryDate',
        'notes',
        'status',
        'total',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
