<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserSubmission;

class UserSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('submissions.review');
    }

    public function view(User $user, UserSubmission $submission): bool
    {
        return $this->viewAny($user);
    }

    public function approve(User $user, UserSubmission $submission): bool
    {
        return $user->hasPermission('submissions.review');
    }

    public function reject(User $user, UserSubmission $submission): bool
    {
        return $user->hasPermission('submissions.review');
    }
}
