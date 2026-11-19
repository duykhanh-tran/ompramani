<?php

namespace App\Models;

use Database\Factories\UserProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'dharma_name',
    'date_of_birth',
    'gender',
    'avatar',
    'preferred_tradition_id',
    'location',
    'bio',
    'dharma_journey_started_at',
    'timezone',
])]
class UserProfile extends Model
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date:Y-m-d',
            'dharma_journey_started_at' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferredTradition(): BelongsTo
    {
        return $this->belongsTo(Tradition::class, 'preferred_tradition_id');
    }

    /**
     * Get the full public URL for the avatar.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if (! $this->avatar) {
                return null;
            }

            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
                return $this->avatar;
            }

            return Storage::disk('public')->url($this->avatar);
        });
    }
}
