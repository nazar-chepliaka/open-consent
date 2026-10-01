<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\DB;

class VaultService
{
    public function createForUser(User $user, string $name): Vault
    {
        return DB::transaction(function () use ($user, $name) {
            $vault = Vault::create([
                'owner_id' => $user->id,
                'name' => $name,
            ]);

            $vault->members()->attach($user->id, ['role' => 'owner']);

            return $vault;
        });
    }
}
