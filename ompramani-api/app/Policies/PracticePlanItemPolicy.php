<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PracticePlan;
use App\Models\PracticePlanItem;
use App\Models\User;

class PracticePlanItemPolicy
{
    /**
     * Determine whether the user can create an item in the plan.
     */
    public function create(User $user, PracticePlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

    /**
     * Determine whether the user can view the item.
     */
    public function view(User $user, PracticePlanItem $item): bool
    {
        return $user->id === $item->user_id;
    }

    /**
     * Determine whether the user can update the item.
     */
    public function update(User $user, PracticePlanItem $item): bool
    {
        return $user->id === $item->user_id;
    }

    /**
     * Determine whether the user can delete the item.
     */
    public function delete(User $user, PracticePlanItem $item): bool
    {
        return $user->id === $item->user_id;
    }
}
