<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory, \App\Traits\BelongsToFillingStation;

    protected $fillable = [
        'filling_station_id',
        'shift_id',
        'name',
        'date',
        'cash_sales',
        'credit_sales',
        'sales_revenue',
        'duration',
        'expiry_date',
        'status',
        'closed_at',
        'approved_at',
        'opened_by',
        'closed_by',
        'approved_by',
        'nozzle_readings',
        'credit_sales_data',
        'cashbacks_data',
        'expenses_data',
        'nozzle_reading_name',
        'additional_readings',
    ];

    protected $casts = [
        'date' => 'date',
        'cash_sales' => 'decimal:2',
        'credit_sales' => 'decimal:2',
        'sales_revenue' => 'decimal:2',
        'expiry_date' => 'datetime',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
        'nozzle_readings' => 'array',
        'credit_sales_data' => 'array',
        'cashbacks_data' => 'array',
        'expenses_data' => 'array',
        'nozzle_reading_name' => 'string',
        'additional_readings' => 'array',
    ];

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class);
    }

    public function dailySales(): HasMany
    {
        return $this->hasMany(DailySale::class);
    }

    public function retailSales(): HasMany
    {
        return $this->hasMany(RetailSale::class);
    }

    public function bulkSales(): HasMany
    {
        return $this->hasMany(BulkSale::class);
    }

    public function closedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'closed_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function openedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'opened_by');
    }
}



