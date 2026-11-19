<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SutraSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'sutra_id',
        'sutra_version_id',
        'parent_id',
        'title',
        'slug',
        'content',
        'position',
        'source_page_start',
        'source_page_end',
    ];

    public function sutra(): BelongsTo
    {
        return $this->belongsTo(Sutra::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(SutraVersion::class, 'sutra_version_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(SutraSection::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(SutraSection::class, 'parent_id')
            ->orderBy('position')
            ->orderBy('id');
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
