<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EditorialStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Story::class);
        return view('admin.stories.index', ['stories' => Story::with(['creator:id,name', 'updater:id,name'])->latest()->paginate(20)]);
    }

    public function create(): View { Gate::authorize('create', Story::class); return view('admin.stories.form', ['story' => new Story(), 'lastEdit' => null]); }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Story::class);
        $story = Story::create($this->validated($request) + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        $this->recordChange($request, $story, 'story.created', 'Создана сказка «'.$story->title.'».');
        Cache::forget('stories:detail:v1:'.$story->slug);
        Cache::forget('stories:genres:v1');
        return redirect()->route('admin.stories.edit', $story)->with('status', 'Сказка сохранена как черновик.');
    }

    public function edit(Story $story): View
    {
        Gate::authorize('update', $story);
        $lastEdit = $this->lastEdit($story);
        return view('admin.stories.form', compact('story', 'lastEdit'));
    }

    public function update(Request $request, Story $story): RedirectResponse
    {
        Gate::authorize('update', $story);
        $story->update($this->validated($request, $story) + ['updated_by' => $request->user()->id]);
        $this->recordChange($request, $story, 'story.updated', 'Обновлена сказка «'.$story->title.'».');
        Cache::forget('stories:detail:v1:'.$story->slug);
        Cache::forget('stories:genres:v1');
        return back()->with('status', 'Изменения сохранены.');
    }

    public function status(Request $request, Story $story): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(EditorialStatus::values())]]);
        if ($data['status'] === 'published') Gate::authorize('publish', Story::class); else Gate::authorize('update', $story);
        $previousStatus = $story->status;
        $story->update(['status' => $data['status'], 'updated_by' => $request->user()->id, 'published_at' => $data['status'] === 'published' ? ($story->published_at ?? now()) : null]);
        $this->recordChange($request, $story, 'story.status_changed', 'Статус сказки «'.$story->title.'»: '.EditorialStatus::labelFor($previousStatus).' → '.EditorialStatus::labelFor($story->status).'.');
        Cache::forget('stories:genres:v1');
        Cache::forget('stories:detail:v1:'.$story->slug);
        return back()->with('status', 'Статус сказки обновлён.');
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
        if (blank($request->input('slug')) && filled($request->input('title'))) {
            $request->merge(['slug' => Str::slug($request->input('title'))]);
        }
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'], 'slug' => ['nullable', 'alpha_dash:ascii', 'max:200', Rule::unique('stories', 'slug')->ignore($story?->id)],
            'author' => ['nullable', 'string', 'max:160'], 'genre' => ['required', 'string', 'max:80'], 'summary' => ['required', 'string', 'max:1200'],
            'body' => ['required', 'string', 'max:500000'], 'age_min' => ['required', 'integer', 'between:0,18'], 'age_max' => ['required', 'integer', 'between:0,18', 'gte:age_min'],
            'reading_minutes' => ['required', 'integer', 'between:1,600'], 'cover_path' => ['nullable', 'string', 'max:255'], 'audio_path' => ['nullable', 'string', 'max:255'], 'seo_title' => ['nullable', 'string', 'max:180'], 'seo_description' => ['nullable', 'string', 'max:320'],
        ]);
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['title']);
        return $data;
    }
}
