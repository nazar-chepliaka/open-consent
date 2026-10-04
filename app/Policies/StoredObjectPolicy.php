<?php

namespace App\Policies;

use App\Models\StoredObject;
use App\Models\User;

class StoredObjectPolicy
{
    public function view(User $user, StoredObject $storedObject): bool
    {
        return $storedObject->vault()
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('members', fn ($query) => $query->whereKey($user->id));
            })
            ->exists();
    }
}
