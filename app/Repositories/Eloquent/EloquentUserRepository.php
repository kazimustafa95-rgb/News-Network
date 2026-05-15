<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function create(array $attributes): User
    {
        return User::create($attributes);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->with(['roles', 'profile', 'subscriptions'])
            ->where('email', $email)
            ->first();
    }

    public function update(User $user, array $attributes): User
    {
        $user->fill($attributes)->save();

        return $user->refresh();
    }

    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'profile', 'subscriptions'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('email', 'like', '%'.$search.'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
