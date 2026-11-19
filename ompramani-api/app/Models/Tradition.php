<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tradition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function sutras(): HasMany
    {
        return $this->hasMany(Sutra::class);
    }

    public function buddhistFigures(): HasMany
    {
        return $this->hasMany(BuddhistFigure::class);
    }

    public function mantras(): HasMany
    {
        return $this->hasMany(Mantra::class);
    }

    public function calendarEvents(): BelongsToMany
    {
        return $this->belongsToMany(CalendarEvent::class, 'calendar_event_traditions')->withTimestamps();
    }

    public function temples(): BelongsToMany
    {
        return $this->belongsToMany(Temple::class, 'temple_traditions')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
