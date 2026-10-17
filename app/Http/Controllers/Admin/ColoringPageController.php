<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\ColoringCategory;
use App\Models\ColoringPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ColoringPageController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $pages = ColoringPage::query()->with('category')->latest()->paginate(25);
        $categories = ColoringCategory::query()->orderBy('name')->get();
        return view('admin.colorings.pages', compact('pages', 'categories'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('coloring_categories', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'alpha_dash:ascii', 'max:180', 'unique:coloring_pages,slug'],
            'preview_path' => ['nullable', 'string', 'max:255'],
            'outline_path' => ['required', 'string', 'max:255'],
        ]);
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : Str::slug($data['title']);
        $data['is_published'] = $request->boolean('is_published');
        $page = ColoringPage::create($data);
        return $this->respond($request, 'Раскраска сохранена.', route('admin.colorings.pages.index', ['created' => $page->id]));
    }

    public function edit(ColoringPage $coloring): View
    {
        $categories = ColoringCategory::query()->orderBy('name')->get();

        return view('admin.colorings.form', compact('coloring', 'categories'));
    }

    public function update(Request $request, ColoringPage $coloring): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('coloring_categories', 'id')],
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'alpha_dash:ascii', 'max:180', Rule::unique('coloring_pages', 'slug')->ignore($coloring->id)],
            'preview_path' => ['nullable', 'string', 'max:255'],
            'outline_path' => ['required', 'string', 'max:255'],
        ]);

        // Keep the established public URL unless an editor explicitly changes it.
        $data['slug'] = filled($data['slug'] ?? null) ? $data['slug'] : $coloring->slug;
        $data['is_published'] = $request->boolean('is_published');
        $coloring->update($data);

        return $this->respond($request, 'Изменения раскраски сохранены.', route('admin.colorings.pages.index'));
    }

    public function destroy(Request $request, ColoringPage $coloring): JsonResponse|RedirectResponse
    {
        $coloring->delete();
        return $this->respond($request, 'Раскраска удалена.');
    }
}
