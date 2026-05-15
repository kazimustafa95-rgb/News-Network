<?php

namespace App\Policies;

use App\Models\NewsPost;
use App\Models\User;

class NewsPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function view(User $user, NewsPost $post): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function update(User $user, NewsPost $post): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function delete(User $user, NewsPost $post): bool
    {
        return $user->hasRole('super_admin');
    }
}
