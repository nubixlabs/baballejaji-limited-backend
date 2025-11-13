<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AccountBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'balance_date',
        'opening_balance',
        'debit_total',
        'credit_total',
        'closing_balance',
    ];

    protected $casts = [
        'balance_date' => 'date',
        'opening_balance' => 'decimal:2',
        'debit_total' => 'decimal:2',
        'credit_total' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    /**
     * Get the account that owns the balance.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Update account balance for a specific date.
     */
    public static function updateBalance(int $accountId, Carbon $date, float $debitAmount = 0, float $creditAmount = 0): AccountBalance
    {
        $balance = static::firstOrCreate(
            [
                'account_id' => $accountId,
                'balance_date' => $date->toDateString(),
            ],
            [
                'opening_balance' => static::getOpeningBalance($accountId, $date),
                'debit_total' => 0,
                'credit_total' => 0,
                'closing_balance' => 0,
            ]
        );

        // Update totals
        $balance->increment('debit_total', $debitAmount);
        $balance->increment('credit_total', $creditAmount);

        // Calculate closing balance based on account type
        $account = Account::find($accountId);
        $netChange = $debitAmount - $creditAmount;
        
        // For asset and expense accounts, debits increase balance
        // For liability, equity, and revenue accounts, credits increase balance
        if (in_array($account->type, ['asset', 'expense'])) {
            $balance->closing_balance = $balance->opening_balance + $balance->debit_total - $balance->credit_total;
        } else {
            $balance->closing_balance = $balance->opening_balance + $balance->credit_total - $balance->debit_total;
        }

        $balance->save();

        return $balance;
    }

    /**
     * Get opening balance for an account on a specific date.
     */
    public static function getOpeningBalance(int $accountId, Carbon $date): float
    {
        $previousBalance = static::where('account_id', $accountId)
            ->where('balance_date', '<', $date->toDateString())
            ->orderBy('balance_date', 'desc')
            ->first();

        return $previousBalance ? $previousBalance->closing_balance : 0;
    }

    /**
     * Get current balance for an account.
     */
    public static function getCurrentBalance(int $accountId): float
    {
        $latestBalance = static::where('account_id', $accountId)
            ->orderBy('balance_date', 'desc')
            ->first();

        return $latestBalance ? $latestBalance->closing_balance : 0;
    }

    /**
     * Get balance for an account on a specific date.
     */
    public static function getBalanceAsOf(int $accountId, Carbon $date): float
    {
        $balance = static::where('account_id', $accountId)
            ->where('balance_date', '<=', $date->toDateString())
            ->orderBy('balance_date', 'desc')
            ->first();

        return $balance ? $balance->closing_balance : 0;
    }
}