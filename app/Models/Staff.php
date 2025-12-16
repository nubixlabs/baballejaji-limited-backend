<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'surname',
        'firstname',
        'othernames',
        'gender',
        'date_of_birth',
        'phone_number',
        'email_address',
        'address',
        'city',
        'state',
        'country',
        'qualification',
        'work_experience',
        'previous_employer',
        'resume',
        'employment_type',
        'currently_employed',
        'date_of_employment',
        'referee_1',
        'referee_2',
        'department_id',
        'designation',
        'tax_id',
        'level_id',
        'next_of_kin_name',
        'next_of_kin_phone',
        'photo',
        'created_by',
        'last_modified_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_of_employment' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastModifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }
}
