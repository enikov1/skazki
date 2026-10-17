<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\ColoringPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ColoringProgressController extends Controller
{
    use RespondsToAjax;

    public function store(Request $request, string $category, string $page): JsonResponse|RedirectResponse
    {
        $coloring = ColoringPage::query()->where('slug', $page)->where('is_published', true)
            ->whereHas('category', fn ($query) => $query->where('slug', $category)->where('is_published', true))
            ->firstOrFail();
        $data = $request->validate([
            'strokes' => ['required', 'array', 'max:600'],
            'strokes.*' => ['required', 'array:color,size,points'],
            'strokes.*.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'strokes.*.size' => ['required', 'numeric', 'min:1', 'max:100'],
            'strokes.*.points' => ['required', 'array', 'max:2000'],
            'strokes.*.points.*' => ['required', 'array', 'size:2'],
            'strokes.*.points.*.0' => ['required', 'numeric', 'between:0,1'],
            'strokes.*.points.*.1' => ['required', 'numeric', 'between:0,1'],
        ]);
        $request->user()->coloringProgress()->updateOrCreate(
            ['coloring_page_id' => $coloring->id],
            ['strokes' => $data['strokes']],
        );
        return $this->respond($request, 'Прогресс раскрашивания сохранён в профиле.');
    }
}
