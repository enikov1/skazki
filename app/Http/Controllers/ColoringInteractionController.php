<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\ColoringPage;
use App\Support\GuestInteraction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ColoringInteractionController extends Controller
{
    use RespondsToAjax;

    public function favorite(Request $request, string $category, string $page): JsonResponse|RedirectResponse
    {
        $coloringPage = $this->publishedPage($category, $page);
        $favorite = $request->user()->coloringFavorites()->where('coloring_page_id', $coloringPage->id)->first();
        if ($favorite) {
            $favorite->delete();
            $saved = false;
        } else {
            $request->user()->coloringFavorites()->create(['coloring_page_id' => $coloringPage->id]);
            $saved = true;
        }
        if ($request->expectsJson()) {
            return response()->json(['message' => $saved ? 'Добавлено в избранное.' : 'Удалено из избранного.', 'saved' => $saved]);
        }
        return $this->respond($request, $saved ? 'Добавлено в избранное.' : 'Удалено из избранного.');
    }

    public function rate(Request $request, string $category, string $page): JsonResponse|RedirectResponse
    {
        $coloringPage = $this->publishedPage($category, $page);
        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5']]);
        $coloringPage->ratings()->updateOrCreate(GuestInteraction::lookup($request), GuestInteraction::identity($request) + ['rating' => $data['rating']]);
        $rating = $coloringPage->ratings()->selectRaw('COALESCE(AVG(rating), 0) AS average, COUNT(*) AS total')->first();
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Ваша оценка сохранена.', 'average' => (float) $rating->average, 'total' => (int) $rating->total, 'user_rating' => $data['rating']]);
        }
        return $this->respond($request, 'Ваша оценка сохранена.');
    }

    private function publishedPage(string $category, string $page): ColoringPage
    {
        return ColoringPage::query()->where('slug', $page)->where('is_published', true)
            ->whereHas('category', fn ($query) => $query->where('slug', $category)->where('is_published', true))
            ->firstOrFail();
    }
}
