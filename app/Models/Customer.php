<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company',
        'contact_person',
        'phone_number',
        'email',
        'address',
        'city',
        'state',
        'country',
        'credit_limit',
        'credit_balance',
        'customer_type',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'credit_balance' => 'decimal:2',
    ];

    public function bulkSales(): HasMany
    {
        return $this->hasMany(BulkSale::class);
    }

    public function retailSales(): HasMany
    {
        return $this->hasMany(RetailSale::class);
    }
}



