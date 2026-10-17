<?php

namespace App\Policies;

use App\Models\InteractiveGame;
use App\Models\User;

class InteractiveGamePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'editor']);
    }

    public function update(User $user, InteractiveGame $game): bool
    {
        return $user->hasRole('admin') || ($user->hasRole('editor') && $game->created_by === $user->id);
    }
}
