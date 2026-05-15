<?php

namespace App\Repositories\Eloquent;

use App\Models\PendingRegistration;
use App\Repositories\Contracts\PendingRegistrationRepositoryInterface;

class EloquentPendingRegistrationRepository implements PendingRegistrationRepositoryInterface
{
    public function updateOrCreateByEmail(string $email, array $attributes): PendingRegistration
    {
        return PendingRegistration::query()->updateOrCreate(
            ['email' => $email],
            $attributes,
        );
    }

    public function findByEmail(string $email): ?PendingRegistration
    {
        return PendingRegistration::query()->where('email', $email)->first();
    }

    public function incrementAttempts(PendingRegistration $pendingRegistration): PendingRegistration
    {
        $pendingRegistration->increment('attempts');

        return $pendingRegistration->refresh();
    }

    public function delete(PendingRegistration $pendingRegistration): void
    {
        $pendingRegistration->delete();
    }

    public function deleteExpired(): int
    {
        return PendingRegistration::query()
            ->where('expires_at', '<', now())
            ->delete();
    }
}
