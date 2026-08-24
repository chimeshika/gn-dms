<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DsDivision extends Model
{
    protected $fillable = ['district_id', 'code', 'name_en', 'name_si', 'name_ta'];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function gnDivisions(): HasMany
    {
        return $this->hasMany(GnDivision::class);
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(Signatory::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class, 'current_ds_division_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name_en;
    }
}
