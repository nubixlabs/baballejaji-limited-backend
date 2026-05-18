<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'username',
        'password',
        'role_id',
        'user_group_id',
        'filling_station_id',
        'last_login_at',
        'last_login_ip',
        'last_login_user_agent',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the role that belongs to the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role?->name === $role;
    }

    /**
     * Check if user is filling station
     */
    public function isFillingStation(): bool
    {
        return $this->hasRole('filling_station');
    }

    /**
     * Check if user is transport
     */
    public function isTransport(): bool
    {
        return $this->hasRole('transport');
    }

    /**
     * Check if user is spare parts
     */
    public function isSpareParts(): bool
    {
        return $this->hasRole('spare_parts');
    }

    /**
     * Get the user group that belongs to the user.
     */
    public function userGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }

    /**
     * Get the filling station that belongs to the user.
     */
    public function fillingStation(): BelongsTo
    {
        return $this->belongsTo(FillingStation::class);
    }

    /**
     * Get the activity logs for the user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the login logs for the user.
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(LoginLog::class);
    }

    /**
     * Log user activity
     */
    public function logActivity(string $action, string $description, array $properties = []): void
    {
        $this->activityLogs()->create([
            'action' => $action,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Log user login
     */
    public function logLogin(): void
    {
        $this->loginLogs()->create([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'login_at' => now(),
        ]);

        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'last_login_user_agent' => request()->userAgent(),
        ]);
    }
    /**
     * The filling stations that belong to the user.
     */
    public function fillingStations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(FillingStation::class);
    }
}
