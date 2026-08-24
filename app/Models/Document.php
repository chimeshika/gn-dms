<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'officer_id',
        'document_type',
        'ref_no',
        'issue_date',
        'file_path',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'document_type' => DocumentType::class,
        ];
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
