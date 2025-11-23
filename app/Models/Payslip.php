<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'emp_id',
        'department_id',
        'level_id',
        'salary_period',
        'slip_name',
        'date_from',
        'date_to',
        'days_worked',
        'total_pay',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'total_pay' => 'decimal:2',
        'days_worked' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'employee_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
