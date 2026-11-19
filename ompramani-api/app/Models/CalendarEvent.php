<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarEventRecurrence;
use App\Enums\CalendarEventStatus;
use App\Enums\CalendarEventType;
use App\Enums\CalendarLeapPolicy;
use App\Enums\CalendarMissingDayPolicy;
use App\Enums\CalendarSystem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'short_description',
        'description',
        'event_type',
        'calendar_system',
        'recurrence_type',
        'solar_month',
        'solar_day',
        'specific_solar_date',
        'solar_feb29_policy',
        'lunar_month',
        'lunar_day',
        'lunar_days_json',
        'specific_lunar_year',
        'lunar_is_leap_month',
        'lunar_leap_policy',
        'missing_day_policy',
        'is_general',
        'sort_order',
        'status',
        'source_title',
        'source_url',
        'source_note',
        'last_verified_at',
        'reviewed_by',
        'reviewed_at',
        'published_at',
    ];

    protected $casts = [
        'event_type' => CalendarEventType::class,
        'calendar_system' => CalendarSystem::class,
        'recurrence_type' => CalendarEventRecurrence::class,
        'lunar_leap_policy' => CalendarLeapPolicy::class,
        'missing_day_policy' => CalendarMissingDayPolicy::class,
        'status' => CalendarEventStatus::class,
        'solar_month' => 'integer',
        'solar_day' => 'integer',
        'specific_solar_date' => 'date',
        'lunar_month' => 'integer',
        'lunar_day' => 'integer',
        'lunar_days_json' => 'array',
        'specific_lunar_year' => 'integer',
        'lunar_is_leap_month' => 'boolean',
        'is_general' => 'boolean',
        'sort_order' => 'integer',
        'last_verified_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    /**
     * Scope query to only published events.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CalendarEventStatus::Published->value)
            ->where(function (Builder $q): void {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Determine if event is published.
     */
    public function isPublished(): bool
    {
        return $this->status === CalendarEventStatus::Published
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function traditions(): BelongsToMany
    {
        return $this->belongsToMany(Tradition::class, 'calendar_event_traditions')->withTimestamps();
    }

    public function buddhistFigures(): BelongsToMany
    {
        return $this->belongsToMany(BuddhistFigure::class, 'calendar_event_buddhist_figures')->withTimestamps();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
