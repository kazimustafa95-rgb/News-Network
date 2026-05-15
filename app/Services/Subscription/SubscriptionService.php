<?php

namespace App\Services\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;

class SubscriptionService
{
    public function __construct(private readonly SubscriptionRepositoryInterface $subscriptions)
    {
    }

    public function status(User $user): ?Subscription
    {
        return $this->subscriptions->currentForUser($user);
    }

    public function verify(User $user, array $attributes): Subscription
    {
        return $this->subscriptions->upsertForUser($user, $attributes + [
            'status' => SubscriptionStatus::Active->value,
            'verified_at' => now(),
            'started_at' => $attributes['started_at'] ?? now(),
            'ends_at' => $attributes['ends_at'] ?? now()->addMonth(),
        ]);
    }

    public function hasActiveEntitlement(User $user): bool
    {
        $subscription = $this->subscriptions->currentForUser($user);

        if (! $subscription) {
            return false;
        }

        return in_array($subscription->status, [SubscriptionStatus::Active, SubscriptionStatus::Grace], true)
            && ($subscription->ends_at === null || $subscription->ends_at->isFuture());
    }
}
