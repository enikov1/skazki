<?php

namespace App\Http\Controllers;

use App\Enums\EditorialStatus;
use App\Models\Story;
use App\Models\CatalogLandingPage;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoryController extends Controller
{
    public function audioIndex(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', 'string', 'max:80'],
            'age' => ['nullable', 'in:3-5,5-8,9-12'],
            'sort' => ['nullable', 'in:available,title'],
        ]);

        $query = Story::published()->whereNotNull('audio_path')
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($sub) => $sub->where('title', 'like', '%'.$q.'%')->orWhere('author', 'like', '%'.$q.'%')))
            ->when($filters['genre'] ?? null, fn ($query, $genre) => $query->where('genre', $genre))
            ->when($filters['age'] ?? null, function ($query, $age): void {
                [$min, $max] = array_map('intval', explode('-', $age));
                $query->where('age_min', '<=', $max)->where('age_max', '>=', $min);
            });

        if (($filters['sort'] ?? 'available') === 'title') {
            $query->orderBy('title');
        } else {
            $query->latest('published_at');
        }

        if ($user = $request->user()) {
            $query->with(['audioPlaybackStates' => fn ($states) => $states->where('user_id', $user->id)]);
        }

        $audioQueue = $request->user()
            ? $request->user()->audioPlaybackStates()->whereNotNull('queue_position')->with('story')->orderBy('queue_position')->get()
                ->filter(fn ($state) => $state->story && $state->story->status === 'published' && $state->story->published_at?->isPast() && filled($state->story->audio_path))
                ->map(function ($state): array {
                    $story = $state->story;
                    return [
                        'id' => $story->slug,
                        'title' => $story->title,
                        'author' => $story->author ?: 'СказкоМир',
                        'cover' => $story->cover_path ? asset($story->cover_path) : asset('assets/illustrations/audio-story.webp'),
                        'src' => route('stories.audio', $story),
                        'download' => $story->allow_download ? route('stories.audio.download', $story) : '',
                        'progressUrl' => route('stories.audio.progress', $story),
                        'position' => $state->position_seconds,
                    ];
                })->values()
            : collect();

        $stories = $query->paginate(12)->withQueryString();
        $genres = Story::published()->whereNotNull('audio_path')->select('genre')->distinct()->orderBy('genre')->pluck('genre');

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('audio.results', compact('stories'))->render(),
                'count' => $stories->total(),
            ]);
        }

        $landingPages = CatalogLandingPage::published()->whereIn('type', ['audio_genre', 'audio_age', 'author'])->orderBy('title')->get();
        return view('audio.index', compact('stories', 'genres', 'filters', 'audioQueue', 'landingPages'));
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'genre' => ['nullable', 'string', 'max:80'], 'age' => ['nullable', 'integer', 'min:0', 'max:18']]);
        $stories = Story::published()->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($sub) => $sub->where('title', 'like', '%'.$q.'%')->orWhere('summary', 'like', '%'.$q.'%')))->when($filters['genre'] ?? null, fn ($query, $genre) => $query->where('genre', $genre))->when($filters['age'] ?? null, fn ($query, $age) => $query->where('age_min', '<=', $age)->where('age_max', '>=', $age))->latest('published_at')->paginate(12)->withQueryString();
        $genres = cache()->remember('stories:genres:v1', 600, fn () => Story::published()->select('genre')->distinct()->orderBy('genre')->pluck('genre'));

        $libraryStats = ['stories' => Story::published()->count(), 'audio' => Story::published()->whereNotNull('audio_path')->count(), 'colorings' => \App\Models\ColoringPage::query()->where('is_published', true)->whereHas('category', fn ($query) => $query->where('is_published', true))->count()];
        $latestComments = \App\Models\Comment::query()->where('status', 'approved')->whereHas('story', fn ($query) => $query->published())->with(['user:id,name', 'story:id,title,slug'])->latest()->limit(3)->get();
        $popularStories = Story::published()->withAvg('ratings', 'rating')->withCount('ratings')->orderByDesc('ratings_count')->orderByDesc('published_at')->limit(5)->get();
        return view('stories.index', compact('stories', 'genres', 'filters', 'libraryStats', 'latestComments', 'popularStories'));
    }

    public function catalog(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', 'string', 'max:80'],
            'age' => ['nullable', 'integer', 'min:0', 'max:18'],
        ]);
        $stories = Story::published()
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($sub) => $sub->where('title', 'like', '%'.$q.'%')->orWhere('summary', 'like', '%'.$q.'%')))
            ->when($filters['genre'] ?? null, fn ($query, $genre) => $query->where('genre', $genre))
            ->when($filters['age'] ?? null, fn ($query, $age) => $query->where('age_min', '<=', $age)->where('age_max', '>=', $age))
            ->latest('published_at')->paginate(18)->withQueryString();
        $genres = cache()->remember('stories:genres:v1', 600, fn () => Story::published()->select('genre')->distinct()->orderBy('genre')->pluck('genre'));

        if ($request->expectsJson()) {
            return response()->json(['html' => view('stories.catalog-results', compact('stories'))->render(), 'count' => $stories->total()]);
        }

        $landingPages = CatalogLandingPage::published()->whereIn('type', ['story_genre', 'age', 'author'])->orderBy('title')->get();
        return view('stories.catalog', compact('stories', 'genres', 'filters', 'landingPages'));
    }

    public function show(Story $story): View
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        return $this->render($story, false);
    }

    public function read(Request $request, Story $story): View
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        $user = $request->user();
        $history = $user?->readingHistories()->where('story_id', $story->id)->first();
        if ($user) {
            $history = $user->readingHistories()->updateOrCreate(['story_id' => $story->id], ['last_read_at' => now()]);
        }

        $preferences = array_merge(['reader_font_size' => 20, 'reader_theme' => 'cream', 'reader_width' => 'medium'], $user?->profile_preferences ?? []);
        $width = ['narrow' => 560, 'medium' => 700, 'wide' => 900][$preferences['reader_width']] ?? 700;

        return view('stories.read', [
            'story' => $story,
            'printLogoUrl' => \App\Models\SiteSetting::printLogoUrl(),
            'readerFontSize' => (int) $preferences['reader_font_size'],
            'readerTheme' => $preferences['reader_theme'],
            'readerWidth' => $width,
            'paragraphPosition' => $history?->paragraph_position ?? 0,
        ]);
    }

    public function saveReadingProgress(Request $request, Story $story)
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        $data = $request->validate(['paragraph' => ['required', 'integer', 'min:0', 'max:10000'], 'completed' => ['sometimes', 'boolean']]);
        $request->user()->readingHistories()->updateOrCreate(
            ['story_id' => $story->id],
            ['paragraph_position' => ($data['completed'] ?? false) ? 0 : $data['paragraph'], 'last_read_at' => now()],
        );

        return response()->json(['saved' => true]);
    }

    public function audio(Story $story)
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        $privateAudio = preg_match('~^audio/[A-Za-z0-9._-]+\\.mp3$~i', (string) $story->audio_path) === 1;
        $publicAudio = preg_match('~^assets/audio/[A-Za-z0-9._-]+\\.mp3$~i', (string) $story->audio_path) === 1;
        abort_unless($privateAudio || $publicAudio, 404);
        $audioFile = $privateAudio ? Storage::disk('local')->path($story->audio_path) : public_path($story->audio_path);
        abort_unless(is_file($audioFile), 404);

        return response()->file($audioFile, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadAudio(Story $story)
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast() && $story->allow_download, 404);
        $privateAudio = preg_match('~^audio/[A-Za-z0-9._-]+\\.mp3$~i', (string) $story->audio_path) === 1;
        $publicAudio = preg_match('~^assets/audio/[A-Za-z0-9._-]+\\.mp3$~i', (string) $story->audio_path) === 1;
        abort_unless($privateAudio || $publicAudio, 404);
        $audioFile = $privateAudio ? Storage::disk('local')->path($story->audio_path) : public_path($story->audio_path);
        abort_unless(is_file($audioFile), 404);

        return response()->download($audioFile, Str::slug($story->title).'.mp3', [
            'Content-Type' => 'audio/mpeg',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function saveAudioProgress(Request $request, Story $story)
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast() && filled($story->audio_path), 404);

        $data = $request->validate([
            'position' => ['required', 'integer', 'min:0', 'max:86400'],
            'completed' => ['sometimes', 'boolean'],
        ]);

        $request->user()->audioPlaybackStates()->updateOrCreate(
            ['story_id' => $story->id],
            ['position_seconds' => ($data['completed'] ?? false) ? 0 : $data['position'], 'updated_at' => now()],
        );

        return response()->json(['saved' => true]);
    }

    public function saveAudioQueue(Request $request)
    {
        $data = $request->validate([
            'stories' => ['present', 'array', 'max:50'],
            'stories.*' => ['required', 'string', 'max:200', 'distinct'],
        ]);
        $slugs = $data['stories'];
        $stories = Story::published()->whereNotNull('audio_path')->whereIn('slug', $slugs)->get()->keyBy('slug');

        if ($stories->count() !== count($slugs)) {
            return response()->json(['message' => 'Очередь содержит недоступную аудиосказку.'], 422);
        }

        $user = $request->user();
        DB::transaction(function () use ($user, $slugs, $stories): void {
            $user->audioPlaybackStates()->whereNotNull('queue_position')->update(['queue_position' => null]);

            foreach ($slugs as $position => $slug) {
                $user->audioPlaybackStates()->updateOrCreate(
                    ['story_id' => $stories[$slug]->id],
                    ['queue_position' => $position, 'updated_at' => now()],
                );
            }
        });

        return response()->json(['saved' => true, 'count' => count($slugs)]);
    }

    public function preview(Story $story): View
    {
        Gate::authorize('update', $story);
        return $this->render($story, true);
    }

    private function render(Story $story, bool $preview): View
    {
        if ($user = auth()->user()) {
            if (! $preview) $user->readingHistories()->updateOrCreate(['story_id' => $story->id], ['last_read_at' => now()]);
        }
        $related = Story::published()->where('genre', $story->genre)->where('id', '<>', $story->getKey())->limit(4)->get(['id', 'slug', 'title', 'author', 'summary', 'cover_path', 'age_min', 'age_max']);
        $comments = $story->comments()->with('user:id,name')->where('status', 'approved')->latest()->paginate(10);
        $rating = $story->ratings()->selectRaw('COALESCE(AVG(rating), 0) AS average, COUNT(*) AS total')->first();
        $userRating = $story->ratings()->where(\App\Support\GuestInteraction::lookup(request()))->value('rating');
        $preferences = array_merge(['reader_font_size' => 20, 'reader_theme' => 'cream', 'reader_width' => 'medium'], auth()->user()?->profile_preferences ?? []);
        $audioProgress = ! $preview && $user && $story->audio_path
            ? (int) $user->audioPlaybackStates()->where('story_id', $story->id)->value('position_seconds')
            : 0;
        $readerFontSize = in_array((int) $preferences['reader_font_size'], [16, 18, 20, 22, 24], true) ? (int) $preferences['reader_font_size'] : 20;
        $readerTheme = in_array($preferences['reader_theme'], ['cream', 'white', 'green'], true) ? $preferences['reader_theme'] : 'cream';
        $readerWidth = ['narrow' => 560, 'medium' => 700, 'wide' => 900][$preferences['reader_width']] ?? 700;
        $statusLabel = EditorialStatus::labelFor($story->status);

        if ($preview) {
            return view('stories.preview', compact('story', 'readerFontSize', 'readerTheme', 'readerWidth', 'statusLabel'));
        }

        $printLogoUrl = \App\Models\SiteSetting::printLogoUrl();
        return view('stories.show', compact('story', 'related', 'comments', 'readerFontSize', 'readerTheme', 'readerWidth', 'audioProgress', 'rating', 'userRating', 'printLogoUrl'));
    }
}
