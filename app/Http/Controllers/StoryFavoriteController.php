<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\Story;
use Illuminate\Http\Request;

class StoryFavoriteController extends Controller
{
    use RespondsToAjax;

    public function store(Request $request, Story $story)
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        $data = $request->validate(['saved' => ['required', 'boolean']]);
        $saved = (bool) $data['saved'];
        if ($saved) {
            $request->user()->storyFavorites()->firstOrCreate(['story_id' => $story->id]);
        } else {
            $request->user()->storyFavorites()->where('story_id', $story->id)->delete();
        }
        $message = $saved ? 'Сказка добавлена в вашу полку.' : 'Сказка убрана из вашей полки.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'saved' => $saved])
            : back()->with('status', $message);
    }
}
