<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PracticeReminder;
use App\Models\User;

class PracticeReminderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PracticeReminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }

    /**
     * Determine whether the user can create models (max 20 active reminders limit).
     */
    public function create(User $user): bool
    {
        $activeCount = $user->practiceReminders()->where('is_enabled', true)->count();

        return $activeCount < 20;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PracticeReminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PracticeReminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }

    /**
     * Determine whether the user can pause the reminder.
     */
    public function pause(User $user, PracticeReminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }

    /**
     * Determine whether the user can resume the reminder.
     */
    public function resume(User $user, PracticeReminder $reminder): bool
    {
        return $user->id === $reminder->user_id;
    }
}
