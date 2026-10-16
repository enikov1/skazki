<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EditorialStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\AuditLog;
use App\Models\Article;
use App\Models\ArticleComment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    use RespondsToAjax;
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', Article::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(EditorialStatus::values())], 'category' => ['nullable', 'string', 'max:100']]);
        $articles = Article::with(['creator:id,name', 'updater:id,name'])
            ->when(filled($filters['q'] ?? null), fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$filters['q'].'%')->orWhere('slug', 'like', '%'.$filters['q'].'%')))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['category'] ?? null), fn ($query) => $query->where('category', $filters['category']))
            ->latest()->paginate(20)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('admin.articles.results', compact('articles'))->render(), 'count' => $articles->total()]);
        }
        $categories = Article::query()->whereNotNull('category')->distinct()->orderBy('category')->pluck('category');
        return view('admin.articles.index', compact('articles', 'filters', 'categories'));
    }

    public function create(): View
    {
        Gate::authorize('create', Article::class);
        return view('admin.articles.form', ['article' => new Article(), 'lastEdit' => null]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', Article::class);
        $article = Article::create($this->validated($request) + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $this->recordChange($request, $article, 'article.created', 'Создана статья «'.$article->title.'».');
        Cache::forget('articles:categories:v1');
        return $this->respond($request, 'Статья сохранена как черновик.', route('admin.articles.edit', $article));
    }

    public function edit(Article $article): View
    {
        Gate::authorize('update', $article);
        $lastEdit = $this->lastEdit($article);
        return view('admin.articles.form', compact('article', 'lastEdit'));
    }

    public function update(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $article);
        $oldSlug = $article->slug;
        $article->update($this->validated($request, $article) + ['updated_by' => $request->user()->id]);
        if ($oldSlug !== $article->slug) {
            $article->slugRedirects()->firstOrCreate(['old_slug' => $oldSlug]);
            $article->slugRedirects()->where('old_slug', $article->slug)->delete();
        }
        $this->recordChange($request, $article, 'article.updated', 'Обновлена статья «'.$article->title.'».');
        Cache::forget('articles:categories:v1');
        return $this->respond($request, 'Изменения сохранены.');
    }

    public function status(Request $request, Article $article): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $article);
        $data = $request->validate(['status' => ['required', Rule::in(EditorialStatus::values())], 'publish_at' => ['nullable', 'date', 'after:now']]);
        if ($data['status'] === 'published') Gate::authorize('publish', Article::class);
        $previousStatus = $article->status;
        $publishAt = $data['publish_at'] ?? ($article->status === 'published' && $article->published_at?->isFuture() ? $article->published_at : now());
        $article->update(['status' => $data['status'], 'updated_by' => $request->user()->id, 'published_at' => $data['status'] === 'published' ? $publishAt : null]);
        $this->recordChange($request, $article, 'article.status_changed', 'Статус статьи «'.$article->title.'»: '.EditorialStatus::labelFor($previousStatus).' → '.EditorialStatus::labelFor($article->status).'.');
        Cache::forget('articles:categories:v1');
        return $this->respond($request, 'Статус статьи обновлён.');
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

    public function moderateComment(Request $request, ArticleComment $comment): RedirectResponse|JsonResponse
    {
        Gate::authorize('moderate', $comment);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $comment->update(['status' => $data['status'], 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        return $this->respond($request, 'Решение по комментарию сохранено.');
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        if (blank($request->input('slug')) && filled($request->input('title'))) $request->merge(['slug' => Str::slug($request->input('title'))]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'alpha_dash:ascii', 'max:200', Rule::unique('articles', 'slug')->ignore($article?->id), function (string $attribute, mixed $value, $fail) use ($article): void {
                $redirect = \App\Models\SlugRedirect::query()->where('redirectable_type', Article::class)->where('old_slug', $value)->when($article, fn ($query) => $query->where('redirectable_id', '!=', $article->id))->exists();
                if ($redirect) $fail('Этот адрес уже используется как перенаправление для другой статьи.');
            }],
            'category' => ['required', 'string', 'max:100'], 'summary' => ['required', 'string', 'max:1200'], 'body' => ['required', 'string', 'max:500000'],
            'reading_minutes' => ['required', 'integer', 'between:1,180'], 'cover_path' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:180'], 'seo_description' => ['nullable', 'string', 'max:320'],
        ]);
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['title']);
        return $data;
    }
}
