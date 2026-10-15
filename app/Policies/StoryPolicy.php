<?php

namespace App\Policies;

use App\Models\Story;
use App\Models\User;

class StoryPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['admin', 'editor']); }
    public function create(User $user): bool { return $user->hasAnyRole(['admin', 'editor']); }
    public function update(User $user, Story $story): bool { return $user->hasRole('admin') || ($user->hasRole('editor') && $story->created_by === $user->id); }
    public function publish(User $user): bool { return $user->hasRole('admin') || $user->hasPermission('stories.publish'); }
}
