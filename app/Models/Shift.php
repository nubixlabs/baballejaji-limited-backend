<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'name',
        'date',
        'cash_sales',
        'credit_sales',
        'sales_revenue',
        'status',
        'closed_at',
        'approved_at',
        'closed_by',
        'approved_by',
    ];

    protected $casts = [
        'date' => 'date',
        'cash_sales' => 'decimal:2',
        'credit_sales' => 'decimal:2',
        'sales_revenue' => 'decimal:2',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function dailySales(): HasMany
    {
        return $this->hasMany(DailySale::class);
    }
}

