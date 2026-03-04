<?php

namespace App\Policies;

use App\Models\SacTicket;
use App\Models\User;

class SacTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'atendente', 'analista', 'vendedor', 'cliente'], true);
    }

    public function view(User $user, SacTicket $ticket): bool
    {
        if (in_array($user->role, ['admin', 'atendente', 'analista', 'vendedor'], true)) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'cliente';
    }

    public function assign(User $user): bool
    {
        return in_array($user->role, ['admin', 'atendente', 'analista', 'vendedor'], true);
    }
}
