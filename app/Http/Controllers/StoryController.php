<?php

namespace App\Http\Controllers;

use App\Enums\EditorialStatus;
use App\Models\Story;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

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

        $stories = $query->paginate(12)->withQueryString();
        $genres = Story::published()->whereNotNull('audio_path')->select('genre')->distinct()->orderBy('genre')->pluck('genre');

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('audio.results', compact('stories'))->render(),
                'count' => $stories->total(),
            ]);
        }

        return view('audio.index', compact('stories', 'genres', 'filters'));
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'genre' => ['nullable', 'string', 'max:80'], 'age' => ['nullable', 'integer', 'min:0', 'max:18']]);
        $stories = Story::published()->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($sub) => $sub->where('title', 'like', '%'.$q.'%')->orWhere('summary', 'like', '%'.$q.'%')))->when($filters['genre'] ?? null, fn ($query, $genre) => $query->where('genre', $genre))->when($filters['age'] ?? null, fn ($query, $age) => $query->where('age_min', '<=', $age)->where('age_max', '>=', $age))->latest('published_at')->paginate(12)->withQueryString();
        $genres = cache()->remember('stories:genres:v1', 600, fn () => Story::published()->select('genre')->distinct()->orderBy('genre')->pluck('genre'));

        return view('stories.index', compact('stories', 'genres', 'filters'));
    }

    public function show(Story $story): View
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        return $this->render($story, false);
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
        $preferences = array_merge(['reader_font_size' => 20, 'reader_theme' => 'cream', 'reader_width' => 'medium'], auth()->user()?->profile_preferences ?? []);
        $readerFontSize = in_array((int) $preferences['reader_font_size'], [16, 18, 20, 22, 24], true) ? (int) $preferences['reader_font_size'] : 20;
        $readerTheme = in_array($preferences['reader_theme'], ['cream', 'white', 'green'], true) ? $preferences['reader_theme'] : 'cream';
        $readerWidth = ['narrow' => 560, 'medium' => 700, 'wide' => 900][$preferences['reader_width']] ?? 700;
        $statusLabel = EditorialStatus::labelFor($story->status);

        if ($preview) {
            return view('stories.preview', compact('story', 'readerFontSize', 'readerTheme', 'readerWidth', 'statusLabel'));
        }

        return view('stories.show', compact('story', 'related', 'comments', 'readerFontSize', 'readerTheme', 'readerWidth'));
    }
}
