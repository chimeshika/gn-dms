<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceHistory extends Model
{
    protected $fillable = [
        'officer_id',
        'event_type',
        'ref_no',
        'description',
        'effective_date',
        'old_value',
        'new_value',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_date' => 'date',
            'event_type' => EventType::class,
        ];
    }

    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
