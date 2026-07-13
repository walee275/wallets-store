<?php

namespace App\Policies;

use App\Models\User;

class AdminAccessPolicy
{
    public function accessAdmin(User $user): bool
    {
        return $user->isAdmin() && $user->is_active;
    }

    public function manageSettings(User $user): bool
    {
        return $user->hasRole('owner');
    }

    public function managePayments(User $user): bool
    {
        return $user->hasRole('owner');
    }

    public function manageOrders(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'staff', 'support']);
    }

    public function manageCatalog(User $user): bool
    {
        return $user->hasAnyRole(['owner', 'staff']);
    }
}
