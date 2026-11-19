<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sutra extends Model
{
    use HasFactory;

    protected $fillable = [
        'tradition_id',
        'title',
        'slug',
        'original_title',
        'translator',
        'language',
        'publisher',
        'publication_year',
        'source',
        'notes',
        'short_description',
        'thumbnail',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function tradition(): BelongsTo
    {
        return $this->belongsTo(Tradition::class);
    }

    public function figures(): BelongsToMany
    {
        return $this->belongsToMany(BuddhistFigure::class, 'buddhist_figure_sutra')->withTimestamps();
    }

    public function versions(): HasMany
    {
        return $this->hasMany(SutraVersion::class);
    }

    public function primaryVersion(): ?SutraVersion
    {
        return $this->versions()
            ->where('status', 'published')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first() ?? $this->versions()->first();
    }

    public function sections(): HasMany
    {
        return $this->hasMany(SutraSection::class)
            ->whereNull('parent_id')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function allSections(): HasMany
    {
        return $this->hasMany(SutraSection::class)
            ->orderBy('position')
            ->orderBy('id');
    }

    public function searchAliases(): MorphMany
    {
        return $this->morphMany(SearchAlias::class, 'searchable');
    }

    public function aliases(): MorphMany
    {
        return $this->searchAliases();
    }

    public function readingProgresses(): HasMany
    {
        return $this->hasMany(SutraReadingProgress::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(SutraBookmark::class);
    }
}
