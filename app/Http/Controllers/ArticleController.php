<?php

namespace App\Http\Controllers;

use App\Enums\EditorialStatus;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'category' => ['nullable', 'string', 'max:100']]);
        $articles = Article::published()
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($sub) => $sub->where('title', 'like', '%'.$q.'%')->orWhere('summary', 'like', '%'.$q.'%')))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->latest('published_at')->paginate(9)->withQueryString();
        $categories = cache()->remember('articles:categories:v1', 600, fn () => Article::published()->select('category')->distinct()->orderBy('category')->pluck('category'));

        if ($request->expectsJson()) {
            return response()->json(['html' => view('articles.results', compact('articles'))->render(), 'count' => $articles->total()]);
        }
        return view('articles.index', compact('articles', 'categories', 'filters'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->status === 'published' && $article->published_at?->isPast(), 404);
        return $this->render($article);
    }

    public function preview(Article $article): View
    {
        Gate::authorize('update', $article);
        return view('articles.preview', ['article' => $article, 'statusLabel' => EditorialStatus::labelFor($article->status)]);
    }

    private function render(Article $article): View
    {
        $comments = $article->comments()->with('user:id,name')->where('status', 'approved')->latest()->paginate(10);
        $related = Article::published()->where('category', $article->category)->where('id', '<>', $article->id)->latest('published_at')->limit(3)->get();
        $rating = $article->ratings()->selectRaw('COALESCE(AVG(rating), 0) AS average, COUNT(*) AS total')->first();
        $userRating = $article->ratings()->where(\App\Support\GuestInteraction::lookup(request()))->value('rating');
        return view('articles.show', compact('article', 'comments', 'related', 'rating', 'userRating'));
    }
}
