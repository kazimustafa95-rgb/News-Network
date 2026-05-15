<?php

namespace App\Repositories\Contracts;

use App\Models\PendingRegistration;

interface PendingRegistrationRepositoryInterface
{
    public function updateOrCreateByEmail(string $email, array $attributes): PendingRegistration;

    public function findByEmail(string $email): ?PendingRegistration;

    public function incrementAttempts(PendingRegistration $pendingRegistration): PendingRegistration;

    public function delete(PendingRegistration $pendingRegistration): void;

    public function deleteExpired(): int;
}
