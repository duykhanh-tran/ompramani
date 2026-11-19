<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MantraVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'mantra_id',
        'name',
        'language',
        'script',
        'content',
        'transliteration',
        'translation',
        'source',
        'notes',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function mantra(): BelongsTo
    {
        return $this->belongsTo(Mantra::class);
    }

    public function recitationAudios(): MorphMany
    {
        return $this->morphMany(RecitationAudio::class, 'audioable');
    }

    public function publishedAudios(): MorphMany
    {
        return $this->recitationAudios()->published();
    }
}
