<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole(['admin', 'editor']); }
    public function create(User $user): bool { return $user->hasAnyRole(['admin', 'editor']); }
    public function update(User $user, Article $article): bool { return $user->hasRole('admin') || ($user->hasRole('editor') && $article->created_by === $user->id); }
    public function publish(User $user): bool { return $user->hasPermission('articles.publish'); }
}

