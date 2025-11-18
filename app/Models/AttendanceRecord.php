<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'hours_from',
        'hours_to',
        'total_hours',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'hours_from' => 'datetime:H:i',
        'hours_to' => 'datetime:H:i',
        'total_hours' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'employee_id');
    }
}
