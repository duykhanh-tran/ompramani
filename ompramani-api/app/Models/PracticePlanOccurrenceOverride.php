<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticePlanOccurrenceOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'practice_plan_item_id',
        'user_id',
        'local_date',
        'schedule_revision',
        'action',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'local_date' => 'date:Y-m-d',
            'schedule_revision' => 'integer',
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
}
