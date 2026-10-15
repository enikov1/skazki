<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'editor']), 403);
        return view('admin.articles.index', ['articles' => Article::latest()->paginate(20)]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'editor']), 403);
        return view('admin.articles.form', ['article' => new Article()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'editor']), 403);
        $article = Article::create($this->validated($request) + ['created_by' => $request->user()->id]);
        Cache::forget('articles:categories:v1');
        return redirect()->route('admin.articles.edit', $article)->with('status', 'Статья сохранена как черновик.');
    }

    public function edit(Article $article): View
    {
        abort_unless($this->canEdit($article), 403);
        return view('admin.articles.form', compact('article'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        abort_unless($this->canEdit($article), 403);
        $article->update($this->validated($request, $article));
        Cache::forget('articles:categories:v1');
        return back()->with('status', 'Изменения сохранены.');
    }

    public function status(Request $request, Article $article): RedirectResponse
    {
        abort_unless($this->canEdit($article), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'review', 'published', 'archived'])]]);
        if ($data['status'] === 'published') abort_unless($request->user()->hasRole('admin') || $request->user()->hasPermission('articles.publish'), 403);
        $article->update(['status' => $data['status'], 'published_at' => $data['status'] === 'published' ? ($article->published_at ?? now()) : null]);
        Cache::forget('articles:categories:v1');
        return back()->with('status', 'Статус статьи обновлён.');
    }

    public function comments(): View
    {
        abort_unless(request()->user()->hasAnyRole(['admin', 'moderator']), 403);
        return view('admin.articles.comments', ['comments' => ArticleComment::with(['user', 'article'])->latest()->paginate(20)]);
    }

    public function moderateComment(Request $request, ArticleComment $comment): RedirectResponse
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'moderator']), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $comment->update(['status' => $data['status'], 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        return back()->with('status', 'Решение по комментарию сохранено.');
    }

    private function canEdit(Article $article): bool
    {
        return request()->user()->hasRole('admin') || (request()->user()->hasRole('editor') && $article->created_by === request()->user()->id);
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        if (blank($request->input('slug')) && filled($request->input('title'))) $request->merge(['slug' => Str::slug($request->input('title'))]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'alpha_dash:ascii', 'max:200', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'category' => ['required', 'string', 'max:100'], 'summary' => ['required', 'string', 'max:1200'], 'body' => ['required', 'string', 'max:500000'],
            'reading_minutes' => ['required', 'integer', 'between:1,180'], 'cover_path' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:180'], 'seo_description' => ['nullable', 'string', 'max:320'],
        ]);
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['title']);
        return $data;
    }
}
