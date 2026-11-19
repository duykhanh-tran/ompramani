<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PrayerResponse;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PrayerResponsePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the response.
     */
    public function view(?User $user, PrayerResponse $response): bool
    {
        // 1. If public and approved (and parent is public approved), anyone can view
        if ($response->isPublicApproved()) {
            return true;
        }

        if (! $user) {
            return false;
        }

        // 2. Admin can view for moderation
        if ($user->role === 'admin') {
            return true;
        }

        // 3. Owner can view own response
        return $user->id === $response->user_id;
    }

    /**
     * Determine whether the user can create responses.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the response.
     */
    public function update(User $user, PrayerResponse $response): bool
    {
        return $user->id === $response->user_id && ! $response->trashed();
    }

    /**
     * Determine whether the user can delete the response.
     */
    public function delete(User $user, PrayerResponse $response): bool
    {
        return $user->id === $response->user_id || $user->role === 'admin';
    }

    /**
     * Determine whether the user can withdraw the response.
     */
    public function withdraw(User $user, PrayerResponse $response): bool
    {
        return $user->id === $response->user_id && $response->withdrawn_at === null;
    }

    /**
     * Determine whether the user can resubmit the response.
     */
    public function resubmit(User $user, PrayerResponse $response): bool
    {
        return $user->id === $response->user_id && $response->isRejected();
    }
}
