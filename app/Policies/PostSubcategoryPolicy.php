<?php

namespace App\Policies;

use App\Models\PostSubcategory;
use App\Models\User;

class PostSubcategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function view(User $user, PostSubcategory $subcategory): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function update(User $user, PostSubcategory $subcategory): bool
    {
        return $user->hasPermission('posts.manage');
    }

    public function delete(User $user, PostSubcategory $subcategory): bool
    {
        return $user->hasPermission('posts.manage');
    }
}
