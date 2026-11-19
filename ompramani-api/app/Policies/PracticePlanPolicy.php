<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PracticePlan;
use App\Models\User;

class PracticePlanPolicy
{
    /**
     * Determine whether the user can view any plans.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the plan.
     */
    public function view(User $user, PracticePlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    /**
     * Determine whether the user can create plans.
     */
    public function create(User $user): bool
    {
        // Enforce maximum 20 non-archived plans per user
        return $user->practicePlans()->where('status', '!=', 'archived')->count() < 20;
    }

    /**
     * Determine whether the user can update the plan.
     */
    public function update(User $user, PracticePlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    /**
     * Determine whether the user can delete the plan.
     */
    public function delete(User $user, PracticePlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    /**
     * Determine whether the user can pause the plan.
     */
    public function pause(User $user, PracticePlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    /**
     * Determine whether the user can resume the plan.
     */
    public function resume(User $user, PracticePlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    /**
     * Determine whether the user can archive the plan.
     */
    public function archive(User $user, PracticePlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }
}
