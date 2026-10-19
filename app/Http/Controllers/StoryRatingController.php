<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\Story;
use App\Support\GuestInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoryRatingController extends Controller
{
    use RespondsToAjax;

    public function store(Request $request, Story $story): RedirectResponse|JsonResponse
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5']]);
        $story->ratings()->updateOrCreate(GuestInteraction::lookup($request), GuestInteraction::identity($request) + ['rating' => $data['rating']]);

        if ($request->expectsJson()) return response()->json(['message' => 'Ваша оценка сказки сохранена.', 'average' => (float) $story->ratings()->avg('rating'), 'total' => $story->ratings()->count(), 'user_rating' => (int) $data['rating']]);
        return $this->respond($request, 'Ваша оценка сказки сохранена.', route('stories.show', $story).'#story-rating', false);
    }
}
