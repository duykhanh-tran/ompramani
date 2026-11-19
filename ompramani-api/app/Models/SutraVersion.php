<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SutraVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'sutra_id',
        'source_document_id',
        'name',
        'translator',
        'language',
        'publisher',
        'publication_year',
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

    public function sutra(): BelongsTo
    {
        return $this->belongsTo(Sutra::class);
    }

    public function sourceDocument(): BelongsTo
    {
        return $this->belongsTo(SourceDocument::class);
    }

    public function documentImport(): HasOne
    {
        return $this->hasOne(DocumentImport::class);
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

    public function readingProgresses(): HasMany
    {
        return $this->hasMany(SutraReadingProgress::class);
    }
}
