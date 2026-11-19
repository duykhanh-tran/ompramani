<?php

namespace App\Models;

use App\Enums\PracticeSessionStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    public function activePracticeSession(): HasOne
    {
        return $this->hasOne(PracticeSession::class)
            ->whereIn('status', [PracticeSessionStatus::Active, PracticeSessionStatus::Paused]);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function sutraReadingProgresses(): HasMany
    {
        return $this->hasMany(SutraReadingProgress::class);
    }

    public function sutraBookmarks(): HasMany
    {
        return $this->hasMany(SutraBookmark::class);
    }

    public function prayerIntentions(): HasMany
    {
        return $this->hasMany(PrayerIntention::class);
    }

    public function prayerParticipations(): HasMany
    {
        return $this->hasMany(PrayerParticipation::class);
    }

    public function prayerResponses(): HasMany
    {
        return $this->hasMany(PrayerResponse::class);
    }

    public function notificationPreferences(): HasOne
    {
        return $this->hasOne(UserNotificationPreference::class);
    }

    public function practiceReminders(): HasMany
    {
        return $this->hasMany(PracticeReminder::class);
    }

    public function practicePlans(): HasMany
    {
        return $this->hasMany(PracticePlan::class);
    }

    public function practicePlanItems(): HasMany
    {
        return $this->hasMany(PracticePlanItem::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class);
    }

    public function getEffectiveNotificationPreferences(): UserNotificationPreference
    {
        return UserNotificationPreference::forUser($this);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
