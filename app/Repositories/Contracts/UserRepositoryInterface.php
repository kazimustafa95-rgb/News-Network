<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    public function create(array $attributes): User;

    public function findByEmail(string $email): ?User;

    public function update(User $user, array $attributes): User;

    public function paginateForAdmin(array $filters = []): LengthAwarePaginator;
}
