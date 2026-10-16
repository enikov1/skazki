<?php

namespace App\Http\Controllers;

use App\Models\ColoringCategory;
use App\Models\ColoringPage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ColoringController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $categories = ColoringCategory::query()->where('is_published', true)
            ->when(filled($filters['q'] ?? null), fn ($query) => $query->where(fn ($sub) => $sub->where('name', 'like', '%'.$filters['q'].'%')->orWhere('description', 'like', '%'.$filters['q'].'%')))
            ->withCount(['pages' => fn ($query) => $query->where('is_published', true)])->orderBy('name')->paginate(12)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('colorings.category-cards', compact('categories'))->render(), 'count' => $categories->total()]);
        }
        return view('colorings.index', compact('categories'));
    }

    public function category(Request $request, ColoringCategory $category): View|JsonResponse
    {
        abort_unless($category->is_published, 404);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $pages = $category->pages()->where('is_published', true)
            ->when(filled($filters['q'] ?? null), fn ($query) => $query->where('title', 'like', '%'.$filters['q'].'%'))
            ->orderBy('title')->paginate(18)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('colorings.page-cards', compact('category', 'pages'))->render(), 'count' => $pages->total()]);
        }
        return view('colorings.category', compact('category', 'pages'));
    }

    public function page(ColoringCategory $category, ColoringPage $page): View
    {
        abort_unless($category->is_published && $page->category_id === $category->id && $page->is_published, 404);
        return view('colorings.page', compact('category', 'page'));
    }
}
