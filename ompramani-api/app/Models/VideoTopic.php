<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class VideoTopic extends Model
{
    use HasFactory;

    protected $table = 'video_topics';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (VideoTopic $topic): void {
            if (empty($topic->slug) && ! empty($topic->name)) {
                $base = Str::slug($topic->name);
                $candidate = $base;
                $counter = 1;
                while (static::where('slug', $candidate)->exists()) {
                    $candidate = "{$base}-{$counter}";
                    $counter++;
                }
                $topic->slug = $candidate;
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(BuddhistVideo::class, 'buddhist_video_topic')
            ->withTimestamps();
    }
}
