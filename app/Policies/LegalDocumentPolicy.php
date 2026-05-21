<?php

namespace App\Policies;

use App\Models\LegalDocument;
use App\Models\User;

class LegalDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pages.manage');
    }

    public function view(User $user, LegalDocument $document): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LegalDocument $document): bool
    {
        return $user->hasPermission('pages.manage');
    }

    public function delete(User $user, LegalDocument $document): bool
    {
        return false;
    }
}
