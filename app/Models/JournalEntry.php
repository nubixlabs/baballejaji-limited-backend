<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_number',
        'transaction_date',
        'description',
        'reference',
        'voucher_id',
        'total_debit',
        'total_credit',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'posted_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    /**
     * Get the voucher that owns the journal entry.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Get the user who created the journal entry.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved the journal entry.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the lines for the journal entry.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class)->orderBy('line_order');
    }

    /**
     * Generate a unique journal number.
     */
    public static function generateJournalNumber(): string
    {
        $prefix = 'JE';
        $date = now()->format('Ymd');
        $lastEntry = static::whereDate('created_at', now()->toDateString())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastEntry ? (int) substr($lastEntry->journal_number, -4) + 1 : 1;
        
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if the journal entry is balanced.
     */
    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    /**
     * Post the journal entry to update account balances.
     */
    public function post(): bool
    {
        if (!$this->isBalanced()) {
            throw new \Exception('Journal entry is not balanced');
        }

        if ($this->status !== 'approved') {
            throw new \Exception('Journal entry must be approved before posting');
        }

        // Update account balances
        foreach ($this->lines as $line) {
            AccountBalance::updateBalance(
                $line->account_id,
                $this->transaction_date,
                $line->debit_amount,
                $line->credit_amount
            );
        }

        $this->update([
            'status' => 'posted',
            'posted_at' => now(),
        ]);

        return true;
    }

    /**
     * Approve the journal entry.
     */
    public function approve(User $approver): bool
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }
}