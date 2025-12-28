<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    protected $fillable = [
        'bulk_sale_id',
        'tank_id',
        'nozzle_id',
        'quantity',
        'destination',
        'sale_date',
        'waybill_no',
        'narration',
        'status',
        'created_by',
        'approved_by',
        'approved_at'
    ];

    public function bulkSale()
    {
        return $this->belongsTo(BulkSale::class);
    }

    public function tank()
    {
        return $this->belongsTo(Tank::class);
    }

    public function nozzle()
    {
        return $this->belongsTo(Nozzle::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
