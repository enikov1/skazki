<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['admin', 'moderator']); }
    public function moderate(User $user, Comment $comment): bool { return $user->hasPermission('comments.moderate'); }
}

