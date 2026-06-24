<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransfer extends Model
{
    use \App\Traits\BelongsToFillingStation;

    protected $fillable = [
        'filling_station_id',
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
