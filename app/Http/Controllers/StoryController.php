<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoryController extends Controller
{
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
        if ($user = auth()->user()) {
            $user->readingHistories()->updateOrCreate(['story_id' => $story->id], ['last_read_at' => now()]);
        }
        $related = Story::published()->where('genre', $story->genre)->where('id', '<>', $story->getKey())->limit(4)->get(['id', 'slug', 'title', 'author', 'summary', 'cover_path', 'age_min', 'age_max']);
        $comments = $story->comments()->with('user:id,name')->where('status', 'approved')->latest()->paginate(10);
        $preferences = array_merge(['reader_font_size' => 20, 'reader_theme' => 'cream', 'reader_width' => 'medium'], auth()->user()?->profile_preferences ?? []);
        $readerFontSize = in_array((int) $preferences['reader_font_size'], [16, 18, 20, 22, 24], true) ? (int) $preferences['reader_font_size'] : 20;
        $readerTheme = in_array($preferences['reader_theme'], ['cream', 'white', 'green'], true) ? $preferences['reader_theme'] : 'cream';
        $readerWidth = ['narrow' => 560, 'medium' => 700, 'wide' => 900][$preferences['reader_width']] ?? 700;

        return view('stories.show', compact('story', 'related', 'comments', 'readerFontSize', 'readerTheme', 'readerWidth'));
    }
}
