<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\UserSubmission;
use App\Repositories\Contracts\SubmissionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentSubmissionRepository implements SubmissionRepositoryInterface
{
    public function create(array $attributes): UserSubmission
    {
        return UserSubmission::create($attributes);
    }

    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return UserSubmission::query()
            ->with(['county', 'videos', 'approvedPost'])
            ->where('user_id', $user->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        return UserSubmission::query()
            ->with(['user.profile', 'county', 'videos', 'reviewer'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['county_id'] ?? null, fn ($query, $countyId) => $query->where('county_id', $countyId))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findById(int $id): ?UserSubmission
    {
        return UserSubmission::query()
            ->with(['user.profile', 'county', 'videos', 'approvedPost'])
            ->find($id);
    }

    public function update(UserSubmission $submission, array $attributes): UserSubmission
    {
        $submission->fill($attributes)->save();

        return $submission->refresh();
    }
}
