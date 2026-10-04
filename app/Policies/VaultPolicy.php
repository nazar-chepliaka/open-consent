<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vault;

class VaultPolicy
{
    public function view(User $user, Vault $vault): bool
    {
        return $this->isMember($user, $vault);
    }

    public function update(User $user, Vault $vault): bool
    {
        return $vault->owner_id === $user->id || $this->hasRole($user, $vault, ['admin']);
    }

    public function delete(User $user, Vault $vault): bool
    {
        return $vault->owner_id === $user->id;
    }

    public function createDocument(User $user, Vault $vault): bool
    {
        return $vault->owner_id === $user->id || $this->hasRole($user, $vault, ['admin', 'editor']);
    }

    private function isMember(User $user, Vault $vault): bool
    {
        return $vault->owner_id === $user->id
            || $vault->members()->whereKey($user->id)->exists();
    }

    private function hasRole(User $user, Vault $vault, array $roles): bool
    {
        return $vault->members()
            ->whereKey($user->id)
            ->wherePivotIn('role', $roles)
            ->exists();
    }
}
