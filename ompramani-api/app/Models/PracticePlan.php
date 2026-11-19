<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PracticePlanStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticePlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'timezone',
        'status',
        'starts_on',
        'ends_on',
    ];

    protected function casts(): array
    {
        return [
            'status' => PracticePlanStatus::class,
            'starts_on' => 'date:Y-m-d',
            'ends_on' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PracticePlanItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PracticePlanStatus::Active);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('status', '!=', PracticePlanStatus::Archived);
    }
}
