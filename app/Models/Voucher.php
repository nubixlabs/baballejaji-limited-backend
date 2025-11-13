<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_number',
        'voucher_date',
        'source_account_id',
        'description',
        'attachment_path',
        'total_amount',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    /**
     * Get the source account for the voucher.
     */
    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'source_account_id');
    }

    /**
     * Get the user who created the voucher.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the voucher.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the line items for the voucher.
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(VoucherLineItem::class)->orderBy('line_order');
    }

    /**
     * Get the journal entry for the voucher.
     */
    public function journalEntry(): HasOne
    {
        return $this->hasOne(JournalEntry::class);
    }

    /**
     * Generate a unique voucher number.
     */
    public static function generateVoucherNumber(): string
    {
        $prefix = 'VCH';
        $date = now()->format('Ymd');
        $lastVoucher = static::whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastVoucher ? (int) substr($lastVoucher->voucher_number, -4) + 1 : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total amount from line items.
     */
    public function calculateTotalAmount(): float
    {
        return $this->lineItems()->sum('amount');
    }

    /**
     * Approve the voucher.
     */
    public function approve(User $approver): bool
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        // Create journal entry
        $this->createJournalEntry();

        return true;
    }

    /**
     * Create journal entry from voucher.
     */
    protected function createJournalEntry(): JournalEntry
    {
        $journalEntry = JournalEntry::create([
            'journal_number' => JournalEntry::generateJournalNumber(),
            'transaction_date' => $this->voucher_date,
            'description' => $this->description ?: "Voucher: {$this->voucher_number}",
            'reference' => $this->voucher_number,
            'voucher_id' => $this->id,
            'created_by' => $this->created_by,
            'status' => 'approved',
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
        ]);

        // Create journal entry lines from voucher line items
        foreach ($this->lineItems as $lineItem) {
            $journalEntry->lines()->create([
                'account_id' => $lineItem->account_id,
                'description' => $lineItem->description,
                'debit_amount' => $lineItem->type === 'debit' ? $lineItem->amount : 0,
                'credit_amount' => $lineItem->type === 'credit' ? $lineItem->amount : 0,
                'line_order' => $lineItem->line_order,
            ]);
        }

        // Update totals
        $journalEntry->update([
            'total_debit' => $journalEntry->lines()->sum('debit_amount'),
            'total_credit' => $journalEntry->lines()->sum('credit_amount'),
        ]);

        return $journalEntry;
    }
}