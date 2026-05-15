<?php

namespace App\Policies;

use App\Models\Advertisement;
use App\Models\User;

class AdvertisementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('ads.manage');
    }

    public function view(User $user, Advertisement $advertisement): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('ads.manage');
    }

    public function update(User $user, Advertisement $advertisement): bool
    {
        return $user->hasPermission('ads.manage');
    }

    public function delete(User $user, Advertisement $advertisement): bool
    {
        return $user->hasRole('super_admin');
    }
}
