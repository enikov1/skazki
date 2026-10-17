<?php

namespace App\Http\Controllers;

use App\Enums\EditorialStatus;
use App\Models\Story;
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

        return view('audio.index', compact('stories', 'genres', 'filters', 'audioQueue'));
    }

    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'genre' => ['nullable', 'string', 'max:80'], 'age' => ['nullable', 'integer', 'min:0', 'max:18']]);
        $stories = Story::published()->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($sub) => $sub->where('title', 'like', '%'.$q.'%')->orWhere('summary', 'like', '%'.$q.'%')))->when($filters['genre'] ?? null, fn ($query, $genre) => $query->where('genre', $genre))->when($filters['age'] ?? null, fn ($query, $age) => $query->where('age_min', '<=', $age)->where('age_max', '>=', $age))->latest('published_at')->paginate(12)->withQueryString();
        $genres = cache()->remember('stories:genres:v1', 600, fn () => Story::published()->select('genre')->distinct()->orderBy('genre')->pluck('genre'));

        return view('stories.index', compact('stories', 'genres', 'filters'));
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
            ->when($filters['age'] ?? null, fn ($query, $age) => $query->where('age_min', '<=', $age)->where('age_max', '>=', $age)