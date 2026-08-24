<?php

namespace App\Models;

use App\Enums\SignatoryCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signatory extends Model
{
    protected $fillable = [
        'category',
        'designation',
        'officer_name',
        'digital_signature_path',
        'ds_division_id',
        'district_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => SignatoryCategory::class,
            'is_active' => 'boolean',
        ];
    }

    public function dsDivision(): BelongsTo
    {
        return $this->belongsTo(DsDivision::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function getJurisdictionLabelAttribute(): string
    {
        if ($this->ds_division_id && $this->dsDivision) {
            return $this->dsDivision->display_name;
        }

        if ($this->district_id && $this->district) {
            return $this->district->display_name;
        }

        return 'National';
    }

    /**
     * Human friendly select label, e.g. "එස්. ආලෝකබණ්ඩාර - ලේකම්".
     */
    public function getSelectLabelAttribute(): string
    {
        return trim($this->officer_name.' - '.$this->designation, ' -');
    }
}
