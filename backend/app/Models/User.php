<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'nic_no',
        'phone',
        'role',
        'ds_division_id',
        'district_id',
        'status',
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
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function officer(): HasOne
    {
        return $this->hasOne(Officer::class);
    }

    public function dsDivision(): BelongsTo
    {
        return $this->belongsTo(DsDivision::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Convenience guard for role scoping inside the application.
     */
    public function isMainAdmin(): bool
    {
        return $this->role === UserRole::MainAdmin;
    }

    public function isDistrictAdmin(): bool
    {
        return $this->role === UserRole::DistrictAdmin;
    }

    public function isDivisionalAdmin(): bool
    {
        return $this->role === UserRole::DivisionalAdmin;
    }

    public function isOfficer(): bool
    {
        return $this->role === UserRole::Officer;
    }
}
