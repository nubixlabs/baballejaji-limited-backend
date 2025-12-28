<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    protected $fillable = [
        'shift_id',
        'amount_transferred',
        'bank',
        'transaction_reference',
        'sent_from',
        'sender_name',
        'details',
        'status',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
