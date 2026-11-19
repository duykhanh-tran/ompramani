<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SutraReadingProgress extends Model
{
    use HasFactory;

    protected $table = 'sutra_reading_progress';

    protected $fillable = [
        'user_id',
        'sutra_id',
        'sutra_version_id',
        'sutra_section_id',
        'last_read_at',
        'revision',
    ];

    protected $casts = [
        'last_read_at' => 'datetime',
        'revision' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sutra(): BelongsTo
    {
        return $this->belongsTo(Sutra::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SutraVersion::class, 'sutra_version_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(SutraSection::class, 'sutra_section_id');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('last_read_at');
    }
}
