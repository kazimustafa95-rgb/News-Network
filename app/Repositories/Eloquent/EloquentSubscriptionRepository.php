<?php

namespace App\Repositories\Eloquent;

use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;

class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function currentForUser(User $user): ?Subscription
    {
        return Subscription::query()
            ->where('user_id', $user->id)
            ->latest('ends_at')
            ->first();
    }

    public function upsertForUser(User $user, array $attributes): Subscription
    {
        return Subscription::updateOrCreate(
            ['provider_transaction_id' => $attributes['provider_transaction_id']],
            $attributes + ['user_id' => $user->id],
        );
    }
}
