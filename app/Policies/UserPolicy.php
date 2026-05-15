<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    public function view(User $user, User $managedUser): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->hasPermission('roles.manage');
    }
}
