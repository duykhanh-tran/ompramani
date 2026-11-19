<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SutraBookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'sutra_id',
        'sutra_version_id',
        'sutra_section_id',
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

    public function scopeSutras(Builder $query): Builder
    {
        return $query->where('type', 'sutra');
    }

    public function scopeSections(Builder $query): Builder
    {
        return $query->where('type', 'section');
    }
}
