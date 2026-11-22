<?php

namespace App\Models;

use App\Models\Concerns\HasPublishedScope;
use App\Services\Media\MediaUrlResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class Mantra extends Model
{
    use HasFactory, HasPublishedScope;

    protected $fillable = [
        'tradition_id',
        'buddhist_figure_id',
        'title',
        'slug',
        'original_title',
        'short_description',
        'original_text',
        'vietnamese_transcription',
        'practice_guidance',
        'thumbnail',
        'audio_path',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Inherit thumbnail from the linked BuddhistFigure, with fallback to legacy thumbnail.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->figure?->thumbnail ?: $this->thumbnail;
    }

    /**
     * Resolve public URL for the chant audio file.
     */
    public function getAudioUrlAttribute(): ?string
    {
        if (empty($this->audio_path)) {
            return null;
        }

        return MediaUrlResolver::resolve($this->audio_path);
    }

    public function tradition(): BelongsTo
    {
        return $this->belongsTo(Tradition::class);
    }

    public function figure(): BelongsTo
    {
        return $this->belongsTo(BuddhistFigure::class, 'buddhist_figure_id');
    }

    public function buddhistFigure(): BelongsTo
    {
        return $this->figure();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MantraVersion::class);
    }

    public function defaultVersion(): HasOne
    {
        return $this->hasOne(MantraVersion::class)->where('is_default', true);
    }

    public function searchAliases(): MorphMany
    {
        return $this->morphMany(SearchAlias::class, 'searchable');
    }

    public function aliases(): MorphMany
    {
        return $this->searchAliases();
    }
}
