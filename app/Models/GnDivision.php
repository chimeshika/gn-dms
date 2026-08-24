<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GnDivision extends Model
{
    protected $fillable = ['ds_division_id', 'code', 'name_en', 'name_si', 'name_ta'];

    public function dsDivision(): BelongsTo
    {
        return $this->belongsTo(DsDivision::class);
    }

    public function officers(): HasMany
    {
        return $this->hasMany(Officer::class, 'current_gn_division_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->code ? $this->code.' - ' : '').$this->name_en);
    }
}
