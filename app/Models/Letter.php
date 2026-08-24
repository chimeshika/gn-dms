<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Letter extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'cc_to'       => 'array',
        'letter_date' => 'date',
    ];

    public function letterBatch(): BelongsTo
    {
        return $this->belongsTo(LetterBatch::class, 'letter_batch_id');
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function signatory(): BelongsTo
    {
        return $this->belongsTo(Signatory::class, 'signatory_id');
    }

    public function controllingOfficer(): BelongsTo
    {
        return $this->belongsTo(Signatory::class, 'controlling_officer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}