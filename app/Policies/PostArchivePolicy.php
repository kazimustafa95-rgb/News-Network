<?php

namespace App\Policies;

use App\Models\PostArchive;
use App\Models\User;

class PostArchivePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('archives.view');
    }

    public function view(User $user, PostArchive $postArchive): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, PostArchive $postArchive): bool
    {
        return $user->hasPermission('archives.view');
    }
}
