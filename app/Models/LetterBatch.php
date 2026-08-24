<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterBatch extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'document_type'            => DocumentType::class,
        'cabinet_app_date'         => 'date',
        'exam_date'                => 'date',
        'probation_effective_date' => 'date',
        'training_complete_date'  => 'date',
        'letter_date'              => 'date',
    ];

    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class, 'letter_batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function dsDivision(): BelongsTo
    {
        return $this->belongsTo(DsDivision::class);
    }
}