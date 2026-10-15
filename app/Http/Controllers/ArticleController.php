<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:100']]);
        $articles = Article::published()
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($sub) => $sub->where('title', 'like', '%'.$q.'%')->orWhere('summary', 'like', '%'.$q.'%')))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->latest('published_at')->paginate(9)->withQueryString();
        $categories = cache()->remember('articles:categories:v1', 600, fn () => Article::published()->select('category')->distinct()->orderBy('category')->pluck('category'));

        return view('articles.index', compact('articles', 'categories', 'filters'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->status === 'published' && $article->published_at?->isPast(), 404);
        $comments = $article->comments()->with('user:id,name')->where('status', 'approved')->latest()->paginate(10);
        $related = Article::published()->where('category', $article->category)->where('id', '<>', $article->id)->latest('published_at')->limit(3)->get();
        $rating = $article->ratings()->selectRaw('COALESCE(AVG(rating), 0) AS average, COUNT(*) AS total')->first();
        $userRating = auth()->id() ? $article->ratings()->where('user_id', auth()->id())->value('rating') : null;

        return view('articles.show', compact('article', 'comments', 'related', 'rating', 'userRating'));
    }
}
