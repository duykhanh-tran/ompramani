<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Media\MediaUrlResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * RecitationAudio represents an independent audio recitation for Buddhist Figures and Mantras (Task 22).
 */
class RecitationAudio extends Model
{
    use HasFactory;

    protected $table = 'recitation_audios';

    protected $fillable = [
        'audioable_type',
        'audioable_id',
        'title',
        'file_path',
        'file_url',
        'mime_type',
        'duration_seconds',
        'performer',
        'source',
        'license_information',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'sort_order' => 'integer',
    ];

    public function audioable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function resolveFileUrl(): string
    {
        $raw = filled($this->file_url) ? $this->file_url : $this->file_path;

        return filled($raw) ? (MediaUrlResolver::resolve($raw) ?? '') : '';
    }
}
