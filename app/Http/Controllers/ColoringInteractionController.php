<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\ColoringPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ColoringInteractionController extends Controller
{
    use RespondsToAjax;

    public function favorite(Request $request, ColoringPage $page): JsonResponse|RedirectResponse
    {
        $this->ensurePublished($page);
        $favorite = $request->user()->coloringFavorites()->where('coloring_page_id', $page->id)->first();
        if ($favorite) {
            $favorite->delete();
            $saved = false;
        } else {
            $request->user()->coloringFavorites()->create(['coloring_page_id' => $page->id]);
            $saved = true;
        }
        if ($request->expectsJson()) {
            return response()->json(['message' => $saved ? 'Добавлено в избранное.' : 'Удалено из избранного.', 'saved' => $saved]);
        }
        return $this->respond($request, $saved ? 'Добавлено в избранное.' : 'Удалено из избранного.');
    }

    public function rate(Request $request, ColoringPage $page): JsonResponse|RedirectResponse
    {
        $this->ensurePublished($page);
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5']]);
        $page->ratings()->updateOrCreate(['user_id' => $request->user()->id], ['rating' => $data['rating']]);
        $rating = $page->ratings()->selectRaw('COALESCE(AVG(rating), 0) AS average, COUNT(*) AS total')->first();
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Ваша оценка сохранена.', 'average' => (float) $rating->average, 'total' => (int) $rating->total, 'user_rating' => $data['rating']]);
        }
        return $this->respond($request, 'Ваша оценка сохранена.');
    }

    private function ensurePublished(ColoringPage $coloring): void
    {
        abort_unless($coloring->is_published && $coloring->category()->where('is_published', true)->exists(), 404);
    }
}
