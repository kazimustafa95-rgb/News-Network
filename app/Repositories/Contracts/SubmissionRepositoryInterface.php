<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\UserSubmission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SubmissionRepositoryInterface
{
    public function create(array $attributes): UserSubmission;

    public function paginateForUser(User $user, array $filters = []): LengthAwarePaginator;

    public function paginateForAdmin(array $filters = []): LengthAwarePaginator;

    public function findById(int $id): ?UserSubmission;

    public function update(UserSubmission $submission, array $attributes): UserSubmission;
}
