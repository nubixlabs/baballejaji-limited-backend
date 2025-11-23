<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'employee_id',
        'emp_id',
        'total_pay',
        'cheque_account',
        'paid_at',
    ];

    protected $casts = [
        'total_pay' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'employee_id');
    }
}
