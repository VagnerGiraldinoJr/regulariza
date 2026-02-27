<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'atendente', 'cliente'], true);
    }

    public function view(User $user, Order $order): bool
    {
        if (in_array($user->role, ['admin', 'atendente'], true)) {
            return true;
        }

        return $user->role === 'cliente' && $order->user_id === $user->id;
    }
}
