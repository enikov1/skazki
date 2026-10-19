<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission('users.manage'); }
    public function changeRole(User $user, User $subject): bool { return $user->hasPermission('users.manage'); }
    public function block(User $user, User $subject): bool { return $user->hasPermission('users.manage'); }
    public function unblock(User $user, User $subject): bool { return $user->hasPermission('users.manage'); }
}

