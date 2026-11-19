<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlmanacMethod extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'key',
        'name',
        'description',
        'version',
        'source_title',
        'source_url',
        'license_details',
        'calendar_timezone',
        'supported_from',
        'supported_to',
        'is_primary',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'supported_from' => 'date:Y-m-d',
            'supported_to' => 'date:Y-m-d',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Scope for active methods.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for primary method.
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }
}
