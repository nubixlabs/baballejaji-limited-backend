<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'bulk_sale_id',
        'payment_id',
        'invoice_number',
        'payment_date',
        'amount',
        'payment_method',
        'paid_by',
        'received_by',
        'details',
        'attachment_path',
        'created_by',
        'approved_by',
        'last_modified_by',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function bulkSale(): BelongsTo
    {
        return $this->belongsTo(BulkSale::class);
    }

    public function parentPayment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'payment_id');
    }

    public function deposits()
    {
        return $this->hasMany(CustomerPayment::class, 'payment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function lastModifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }
}
