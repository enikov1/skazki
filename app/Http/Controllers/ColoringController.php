<?php

namespace App\Http\Controllers;

use App\Models\ColoringCategory;
use App\Models\ColoringPage;
use Illuminate\View\View;

class ColoringController extends Controller
{
    public function index(): View
    {
        $categories = ColoringCategory::query()->where('is_published', true)->withCount(['pages' => fn ($query) => $query->where('is_published', true)])->orderBy('name')->get();
        return view('colorings.index', compact('categories'));
    }

    public function category(ColoringCategory $category): View
    {
        abort_unless($category->is_published, 404);
        $pages = $category->pages()->where('is_published', true)->orderBy('title')->paginate(18);
        return view('colorings.category', compact('category', 'pages'));
    }

    public function page(ColoringCategory $category, ColoringPage $page): View
    {
        abort_unless($category->is_published && $page->category_id === $category->id && $page->is_published, 404);
        return view('colorings.page', compact('category', 'page'));
    }
}
