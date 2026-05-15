<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('subscriptions.view');
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->hasPermission('roles.manage');
    }
}
