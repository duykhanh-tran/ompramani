<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticePlanOccurrenceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'practice_plan_item_id',
        'user_id',
        'occurrence_local_date',
        'schedule_revision',
        'practice_session_id',
        'linked_at',
    ];

    protected function casts(): array
    {
        return [
            'occurrence_local_date' => 'date:Y-m-d',
            'schedule_revision' => 'integer',
            'linked_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(PracticePlanItem::class, 'practice_plan_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(PracticeSession::class, 'practice_session_id');
    }
}
