<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->role === 'admin' || $user->id === $order->user_id;
    }

    /**
     * Determine whether the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return in_array($order->order_status->value, ['pending', 'confirmed'], true);
        }

        return $user->id === $order->user_id && $order->canBeCancelledByCustomer();
    }

    /**
     * Determine whether the user can process/transition orders in admin panel.
     */
    public function process(User $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can update internal notes.
     */
    public function update(User $user): bool
    {
        return $user->role === 'admin';
    }
}
