<?php

namespace App\Policies;

use App\Models\ArticleComment;
use App\Models\User;

class ArticleCommentPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['admin', 'moderator']); }
    public function moderate(User $user, ArticleComment $comment): bool { return $user->hasPermission('comments.moderate'); }
}

