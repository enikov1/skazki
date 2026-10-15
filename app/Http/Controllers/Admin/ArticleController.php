<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EditorialStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Support\Facades\Gate;
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
        Gate::authorize('viewAny', Article::class);
        return view('admin.articles.index', ['articles' => Article::with(['creator:id,name', 'updater:id,name'])->latest()->paginate(20)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Article::class);
        return view('admin.articles.form', ['article' => new Article(), 'lastEdit' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Article::class);
        $article = Article::create($this->validated($request) + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $this->recordChange($request, $article, 'article.created', 'Создана статья «'.$article->title.'».');
        Cache::forget('articles:categories:v1');
        return redirect()->route('admin.articles.edit', $article)->with('status', 'Статья сохранена как черновик.');
    }

    public function edit(Article $article): View
    {
        Gate::authorize('update', $article);
        $lastEdit = $this->lastEdit($article);
        return view('admin.articles.form', compact('article', 'lastEdit'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        Gate::authorize('update', $article);
        $article->update($this->validated($request, $article) + ['updated_by' => $request->user()->id]);
        $this->recordChange($request, $article, 'article.updated', 'Обновлена статья «'.$article->title.'».');
        Cache::forget('articles:categories:v1');
        return back()->with('status', 'Изменения сохранены.');
    }

    public function status(Request $request, Article $article): RedirectResponse
    {
        Gate::authorize('update', $article);
        $data = $request->validate(['status' => ['required', Rule::in(EditorialStatus::values())]]);
        if ($data['status'] === 'published') Gate::authorize('publish', Article::class);
        $previousStatus = $article->status;
        $article->update(['status' => $data['status'], 'updated_by' => $request->user()->id, 'published_at' => $data['status'] === 'published' ? ($article->published_at ?? now()) : null]);
        $this->recordChange($request, $article, 'article.status_changed', 'Статус статьи «'.$article->title.'»: '.EditorialStatus::labelFor($previousStatus).' → '.EditorialStatus::labelFor($article->status).'.');
        Cache::forget('articles:categories:v1');
        return back()->with('status', 'Статус статьи обновлён.');
    }

    public function preview(Article $article): View
    {
        Gate::authorize('update', $article);
        return app(\App\Http\Controllers\ArticleController::class)->preview($article);
    }

    private function lastEdit(Article $article): ?AuditLog
    {
        return AuditLog::with('actor')->where('subject_type', Article::class)->where('subject_id', $article->id)->latest('created_at')->first();
    }

    private function recordChange(Request $request, Article $article, string $action, string $summary): void
    {
        AuditLog::create(['actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => Article::class, 'subject_id' => $article->id, 'summary' => $summary, 'created_at' => now()]);
    }

    public function comments(): View
    {
        Gate::authorize('viewAny', ArticleComment::class);
        return view('admin.articles.comments', ['comments' => ArticleComment::with(['user', 'article'])->latest()->paginate(20)]);
    }

    public function moderateComment(Request $request, ArticleComment $comment): RedirectResponse
    {
        Gate::authorize('moderate', $comment);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $comment->update(['status' => $data['status'], 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        return back()->with('status', 'Решение по комментарию сохранено.');
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
