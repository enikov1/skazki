<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\ColoringCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ColoringCategoryController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $categories = ColoringCategory::query()->withCount('pages')->orderBy('name')->get();
        return view('admin.colorings.categories', compact('categories'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'alpha_dash:ascii', 'max:120', 'unique:coloring_categories,slug'],
            'description' => ['nullable', 'string', 'max:500'],
            'cover_path' => ['nullable', 'string', 'max:255'],
        ]);
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['name']);
        $data['is_published'] = $request->boolean('is_published');
        ColoringCategory::create($data);
        return $this->respond($request, 'Категория раскрасок сохранена.', route('admin.coloring-categories.index'));
    }

    public function update(Request $request, ColoringCategory $category): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:120', Rule::unique('coloring_categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'is_published' => ['required', 'boolean'],
        ]);
        $category->update($data);
        return $this->respond($request, 'Категория обновлена.');
    }

    public function destroy(Request $request, ColoringCategory $category): JsonResponse|RedirectResponse
    {
        if ($category->pages()->exists()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Сначала удалите или перенесите раскраски из категории.', 'errors' => ['category' => ['Сначала удалите или перенесите раскраски из категории.']]], 422)
                : back()->withErrors(['category' => 'Сначала удалите или перенесите раскраски из категории.']);
        }
        $category->delete();
        return $this->respond($request, 'Категория удалена.');
    }
}
