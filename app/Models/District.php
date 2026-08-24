<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['code', 'name_en', 'name_si', 'name_ta'];

    public function dsDivisions(): HasMany
    {
        return $this->hasMany(DsDivision::class);
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(Signatory::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class, 'current_district_id');
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
