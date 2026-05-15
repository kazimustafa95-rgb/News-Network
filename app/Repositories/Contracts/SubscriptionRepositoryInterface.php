<?php

namespace App\Repositories\Contracts;

use App\Models\Subscription;
use App\Models\User;

interface SubscriptionRepositoryInterface
{
    public function currentForUser(User $user): ?Subscription;

    public function upsertForUser(User $user, array $attributes): Subscription;
}
