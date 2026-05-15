<?php

namespace App\Policies;

use App\Models\PostCategory;
use App\Models\User;

class PostCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function view(User $user, PostCategory $category): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function update(User $user, PostCategory $category): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function delete(User $user, PostCategory $category): bool
    {
        return $user->hasPermission('posts.manage');
    }
}
