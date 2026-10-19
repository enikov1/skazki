<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EditorialStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\AuditLog;
use App\Models\Genre;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoryController extends Controller
{
    use RespondsToAjax;
    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', Story::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(EditorialStatus::values())], 'genre' => ['nullable', 'string', 'max:80', Rule::exists('genres', 'name')]]);
        $stories = Story::with(['creator:id,name', 'updater:id,name'])
            ->when(filled($filters['q'] ?? null), fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$filters['q'].'%')->orWhere('slug', 'like', '%'.$filters['q'].'%')))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['genre'] ?? null), fn ($query) => $query->where('genre', $filters['genre']))
            ->latest()->paginate(20)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('admin.stories.results', compact('stories'))->render(), 'count' => $stories->total()]);
        }
        $genres = Genre::query()->orderBy('name')->pluck('name');
        return view('admin.stories.index', compact('stories', 'filters', 'genres'));
    }

    public function create(): View { Gate::authorize('create', Story::class); return view('admin.stories.form', ['story' => new Story(), 'lastEdit' => null, 'genres' => Genre::query()->orderBy('name')->get()]); }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', Story::class);
        $story = Story::create($this->validated($request) + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $this->recordChange($request, $story, 'story.created', 'Создана сказка «'.$story->title.'».');
        Cache::forget('stories:detail:v1:'.$story->slug);
        Cache::forget('stories:genres:v1');
        return $this->respond($request, 'Сказка сохранена как черновик.', route('admin.stories.edit', $story));
    }

    public function edit(Story $story): View
    {
        Gate::authorize('update', $story);
        $lastEdit = $this->lastEdit($story);
        $genres = Genre::query()->orderBy('name')->get();
        return view('admin.stories.form', compact('story', 'lastEdit', 'genres'));
    }

    public function update(Request $request, Story $story): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $story);
        $oldSlug = $story->slug;
        $story->update($this->validated($request, $story) + ['updated_by' => $request->user()->id]);
        if ($oldSlug !== $story->slug) {
            $story->slugRedirects()->firstOrCreate(['old_slug' => $oldSlug]);
            $story->slugRedirects()->where('old_slug', $story->slug)->delete();
        }
        $this->recordChange($request, $story, 'story.updated', 'Обновлена сказка «'.$story->title.'».');
        Cache::forget('stories:detail:v1:'.$story->slug);
        Cache::forget('stories:detail:v1:'.$oldSlug);
        Cache::forget('stories:genres:v1');
        return $this->respond($request, 'Изменения сохранены.');
    }

    public function status(Request $request, Story $story): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(EditorialStatus::values())], 'publish_at' => ['nullable', 'date', 'after:now']]);
        if ($data['status'] === 'published') Gate::authorize('publish', Story::class); else Gate::authorize('update', $story);
        $previousStatus = $story->status;
        $publishAt = $data['publish_at'] ?? ($story->status === 'published' && $story->published_at?->isFuture() ? $story->published_at : now());
        $story->update(['status' => $data['status'], 'updated_by' => $request->user()->id, 'published_at' => $data['status'] === 'published' ? $publishAt : null]);
        $this->recordChange($request, $story, 'story.status_changed', 'Статус сказки «'.$story->title.'»: '.EditorialStatus::labelFor($previousStatus).' → '.EditorialStatus::labelFor($story->status).'.');
        Cache::forget('stories:genres:v1');
        Cache::forget('stories:detail:v1:'.$story->slug);
        return $this->respond($request, 'Статус сказки обновлён.');
    }

    public function preview(Story $story): View
    {
        Gate::authorize('update', $story);
        return app(\App\Http\Controllers\StoryController::class)->preview($story);
    }

    private function lastEdit(Story $story): ?AuditLog
    {
        return AuditLog::with('actor')->where('subject_type', Story::class)->where('subject_id', $story->id)->latest('created_at')->first();
    }

    private function recordChange(Request $request, Story $story, string $action, string $summary): void
    {
        AuditLog::create(['actor_id' => $request->user()->id, 'action' => $action, 'subject_type' => Story::class, 'subject_id' => $story->id, 'summary' => $summary, 'created_at' => now()]);
    }

    private function validated(Request $request, ?Story $story = null): array
    {
        if (blank($request->input('slug')) && is_string($request->input('title')) && filled($request->input('title'))) {
            $request->merge(['slug' => Str::slug($request->input('title'))]);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'alpha_dash:ascii', 'max:200', Rule::unique('stories', 'slug')->ignore($story?->id), function (string $attribute, mixed $value, $fail) use ($story): void {
                $redirect = \App\Models\SlugRedirect::query()->where('redirectable_type', Story::class)->where('old_slug', $value)->when($story, fn ($query) => $query->where('redirectable_id', '!=', $story->id))->exists();
                if ($redirect) $fail('Этот адрес уже используется как перенаправление для другой сказки.');
            }],
            'author' => ['nullable', 'string', 'max:160'], 'series_name' => ['nullable', 'string', 'max:160'],
            'source_name' => ['nullable', 'string', 'max:180'], 'source_url' => ['nullable', 'url', 'max:500', function (string $attribute, mixed $value, $fail): void {
                if (filled($value) && ! in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)) {
                    $fail('Ссылка на источник должна начинаться с http:// или https://.');
                }
            }],
            'rights_holder' => ['nullable', 'string', 'max:180'], 'rights_note' => ['nullable', 'string', 'max:4000'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'genre' => ['required', 'string', 'max:80', Rule::exists('genres', 'name')], 'summary' => ['required', 'string', 'max:1200'],
            'body' => ['required', 'string', 'max:500000'], 'age_min' => ['required', 'integer', 'between:0,18'], 'age_max' => ['required', 'integer', 'between:0,18', 'gte:age_min'],
            'reading_minutes' => ['required', 'integer', 'between:1,600'], 'cover_path' => ['nullable', 'string', 'max:255'],
            'audio_path' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, $fail): void {
                if (blank($value)) return;
                if (preg_match('~^audio/[A-Za-z0-9._-]+\\.mp3$~i', $value) && Storage::disk('local')->exists($value)) return;
                if (preg_match('~^assets/audio/[A-Za-z0-9._-]+\\.mp3$~i', $value) && is_file(public_path($value))) return;
                $fail('Выберите загруженный MP3 из медиатеки.');
            }],
            'allow_download' => ['required', 'boolean'],
            'seo_title' => ['nullable', 'string', 'max:180'], 'seo_description' => ['nullable', 'string', 'max:320'],
        ]);
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['title']);
        $data['allow_download'] = filled($data['audio_path'] ?? null) && $request->boolean('allow_download');
        $data['tags'] = collect(explode(',', $data['tags'] ?? ''))
            ->map(fn (string $tag) => trim($tag))
            ->filter()
            ->unique(fn (string $tag) => mb_strtolower($tag))
            ->take(20)
            ->values()
            ->all();
        return $data;
    }
}
