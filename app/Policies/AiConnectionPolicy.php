<?php

namespace App\Policies;

use App\Models\AiConnection;
use App\Models\User;

class AiConnectionPolicy
{
    public function view(User $user, AiConnection $aiConnection): bool
    {
        return $aiConnection->user_id === $user->id;
    }

    public function update(User $user, AiConnection $aiConnection): bool
    {
        return $this->view($user, $aiConnection);
    }

    public function delete(User $user, AiConnection $aiConnection): bool
    {
        return $this->view($user, $aiConnection);
    }

    public function test(User $user, AiConnection $aiConnection): bool
    {
        return $this->view($user, $aiConnection);
    }
}
