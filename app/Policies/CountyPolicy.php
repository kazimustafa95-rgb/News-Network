<?php

namespace App\Policies;

use App\Models\County;
use App\Models\User;

class CountyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('counties.manage');
    }

    public function view(User $user, County $county): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('counties.manage');
    }

    public function update(User $user, County $county): bool
    {
        return $user->hasPermission('counties.manage');
    }
}
