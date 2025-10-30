<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contactPerson',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'paymentTerms',
        'notes',
        'rating',
        'totalOrders',
        'totalValue',
        'lastOrderDate',
        'status',
    ];

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}
