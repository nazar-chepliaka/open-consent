<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($document->isPublic()) {
            return true;
        }

        return $document->ownerVault()
            ->whereHas('members', fn ($query) => $query->whereKey($user->id))
            ->exists();
    }

    public function update(User $user, Document $document): bool
    {
        if ($document->isPublic()) {
            return false;
        }

        return $document->ownerVault()
            ->whereHas('members', fn ($query) => $query->whereKey($user->id)->wherePivotIn('role', ['owner', 'admin', 'editor']))
            ->exists();
    }
}
