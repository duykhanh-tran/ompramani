<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PrayerIntention;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrayerIntentionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the intention.
     */
    public function view(?User $user, PrayerIntention $intention): bool
    {
        // 1. If public and approved, anyone (including guests) can view
        if ($intention->isPublicApproved()) {
            return true;
        }

        // 2. Otherwise, user must be authenticated
        if (! $user) {
            return false;
        }

        // 3. Admin can view for moderation
        if ($user->role === 'admin') {
            return true;
        }

        // 4. Owner can view own intention
        return $user->id === $intention->user_id;
    }

    /**
     * Determine whether the user can create intentions.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the intention.
     */
    public function update(User $user, PrayerIntention $intention): bool
    {
        return $user->id === $intention->user_id;
    }

    /**
     * Determine whether the user can delete the intention.
     */
    public function delete(User $user, PrayerIntention $intention): bool
    {
        return $user->id === $intention->user_id || $user->role === 'admin';
    }

    /**
     * Determine whether the user can submit the intention for review.
     */
    public function submitForReview(User $user, PrayerIntention $intention): bool
    {
        return $user->id === $intention->user_id;
    }

    /**
     * Determine whether the user can withdraw the intention.
     */
    public function withdraw(User $user, PrayerIntention $intention): bool
    {
        return $user->id === $intention->user_id;
    }

    /**
     * Determine whether the user can moderate intentions (approve, reject, revoke).
     */
    public function moderate(User $user): bool
    {
        return $user->role === 'admin';
    }
}
