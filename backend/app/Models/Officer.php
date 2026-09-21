<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\Medium;
use App\Enums\OfficerGrade;
use App\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Officer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'dependents' => 'array',
        'dob'                    => 'date',
        'first_appointment_date' => 'date',
        'current_grade'          => OfficerGrade::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'current_district_id');
    }

    public function dsDivision(): BelongsTo
    {
        return $this->belongsTo(DsDivision::class, 'current_ds_division_id');
    }

    public function gnDivision(): BelongsTo
    {
        return $this->belongsTo(GnDivision::class, 'current_gn_division_id');
    }

    public function serviceHistories(): HasMany
    {
        return $this->hasMany(ServiceHistory::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function districtRelation(): BelongsTo
    {
        return $this->district();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name_en ?? $this->nic_no ?? '#' . $this->id;
    }

    /**
     * Resolve raw text values to standard gender values safely.
     */
    public static function resolveGender(mixed $value): string
    {
        if (!$value) {
            return 'Male';
        }

        $val = strtolower(trim((string) $value));

        if (in_array($val, ['mrs', 'miss', 'ms', 'female', 'f'])) {
            return 'Female';
        }

        return 'Male';
    }

    /**
     * Resolve raw text values to standard medium values safely.
     */
    public static function resolveMedium(mixed $value): string
    {
        if (!$value) {
            return 'Sinhala';
        }

        $val = strtolower(trim((string) $value));

        if (str_contains($val, 'tam') || $val === 'ta') {
            return 'Tamil';
        }

        if (str_contains($val, 'eng') || $val === 'en') {
            return 'English';
        }

        return 'Sinhala';
    }
}
