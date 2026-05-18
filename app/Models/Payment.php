<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'payment_date',
        'supplier_id',
        'purchase_id',
        'amount',
        'payment_method',
        'reference_number',
        'shift_id',
        'sales_revenue',
        'paid_by',
        'received_by',
        'details',
        'attachment_path',
        'status',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'sales_revenue' => 'decimal:2',
    ];

    /**
     * Get the supplier that owns the payment.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the shift associated with the payment.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    /**
     * Get the purchase that this payment is for.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the user who created the payment.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate a unique payment number.
     */
    public static function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $lastPayment = static::whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastPayment ? (int) substr($lastPayment->payment_number, -4) + 1 : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}