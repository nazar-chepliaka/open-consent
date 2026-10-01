<?php

namespace App\Policies;

use App\Models\StoredObject;
use App\Models\User;

class StoredObjectPolicy
{
    public function view(User $user, StoredObject $storedObject): bool
    {
        return $storedObject->vault()
            ->whereHas('members', fn ($query) => $query->whereKey($user->id))
            ->exists();
    }
}
