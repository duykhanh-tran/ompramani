<?php

namespace App\Models;

use App\Models\Concerns\HasPublishedScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BuddhistFigure extends Model
{
    use HasFactory, HasPublishedScope;

    protected $fillable = [
        'tradition_id',
        'name',
        'slug',
        'original_name',
        'invocation_text',
        'figure_type',
        'short_description',
        'biography',
        'vows_introduction',
        'great_vows',
        'iconography',
        'practice_guidance',
        'thumbnail',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function tradition(): BelongsTo
    {
        return $this->belongsTo(Tradition::class);
    }

    public function traditions(): BelongsToMany
    {
        return $this->belongsToMany(Tradition::class, 'buddhist_figure_tradition')->withTimestamps();
    }

    public function mantras(): HasMany
    {
        return $this->hasMany(Mantra::class);
    }

    public function sutras(): BelongsToMany
    {
        return $this->belongsToMany(Sutra::class, 'buddhist_figure_sutra')->withTimestamps();
    }

    public function vows(): HasMany
    {
        return $this->hasMany(BuddhistFigureVow::class)->orderBy('sort_order')->orderBy('id');
    }

    public function recitationAudios(): MorphMany
    {
        return $this->morphMany(RecitationAudio::class, 'audioable');
    }

    public function publishedAudios(): MorphMany
    {
        return $this->recitationAudios()->published();
    }

    public function searchAliases(): MorphMany
    {
        return $this->morphMany(SearchAlias::class, 'searchable');
    }

    public function aliases(): MorphMany
    {
        return $this->searchAliases();
    }

    public function calendarEvents(): BelongsToMany
    {
        return $this->belongsToMany(CalendarEvent::class, 'calendar_event_buddhist_figures')->withTimestamps();
    }
}
