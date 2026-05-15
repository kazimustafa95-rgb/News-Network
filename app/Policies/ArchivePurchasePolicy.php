<?php

namespace App\Policies;

use App\Models\ArchivePurchase;
use App\Models\User;

class ArchivePurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('archives.view');
    }

    public function view(User $user, ArchivePurchase $archivePurchase): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ArchivePurchase $archivePurchase): bool
    {
        return $user->hasPermission('roles.manage');
    }
}
