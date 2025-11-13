<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'account_id',
        'description',
        'amount',
        'type',
        'line_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Get the voucher that owns the line item.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Get the account for the line item.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}