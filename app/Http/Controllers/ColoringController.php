<?php

namespace App\Http\Controllers;

use App\Models\ColoringCategory;
use App\Models\ColoringPage;
use App\Models\ColoringSubmission;
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
            ->withAvg('ratings', 'rating')->withCount('ratings')
            ->withExists(['favorites as is_favorited' => fn ($query) => $query->where('user_id', $request->user()?->id)])
            ->orderBy('title')->paginate(18)->withQueryString();
        if ($request->expectsJson()) {
            return response()->json(['html' => view('colorings.page-cards', compact('category', 'pages'))->render(), 'count' => $pages->total()]);
        }
        return view('colorings.category', compact('category', 'pages'));
    }

    public function page(ColoringCategory $category, ColoringPage $page): View
    {
        abort_unless($category->is_published && $page->category_id === $category->id && $page->is_published, 404);
        $rating = $page->ratings()->selectRaw('COALESCE(AVG(rating), 0) AS average, COUNT(*) AS total')->first();
        $userRating = $page->ratings()->where(\App\Support\GuestInteraction::lookup(request()))->value('rating');
        $isFavorited = auth()->user()?->coloringFavorites()->where('coloring_page_id', $page->id)->exists() ?? false;
        $savedStrokes = auth()->user()?->coloringProgress()->where('coloring_page_id', $page->id)->first()?->strokes ?? [];
        $comments = $page->comments()->where('status', 'approved')->latest()->paginate(10);
        $related = $category->pages()->where('is_published', true)->where('id', '<>', $page->id)
            ->with('category:id,name,slug')->withAvg('ratings', 'rating')->withCount('favorites')
            ->orderByDesc('favorites_count')->orderByDesc('ratings_avg_rating')->orderBy('title')->limit(4)->get();
        if ($related->count() < 4) {
            $excludedIds = $related->pluck('id')->push($page->id);
            $popular = ColoringPage::query()->where('is_published', true)->whereNotIn('id', $excludedIds)
                ->whereHas('category', fn ($query) => $query->where('is_published', true))
                ->with('category:id,name,slug')->withAvg('ratings', 'rating')->withCount('favorites')
                ->orderByDesc('favorites_count')->orderByDesc('ratings_avg_rating')->orderBy('title')
                ->limit(4 - $related->count())->get();
            $related = $related->concat($popular);
        }

        return view('colorings.page', compact('category', 'page', 'rating', 'userRating', 'isFavorited', 'savedStrokes', 'related', 'comments'));
    }

    public function gallery(): View
    {
        $works = ColoringSubmission::query()->where('status', 'approved')->latest()->paginate(18);
        return view('colorings.gallery', compact('works'));
    }

    public function galleryImage(ColoringSubmission $submission)
    {
        abort_unless($submission->status === 'approved', 404);
        return $this->privateImage($submission);
    }

    public function galleryWork(ColoringSubmission $submission): View
    {
        abort_unless($submission->status === 'approved', 404);
        $comments = $submission->comments()->where('status', 'approved')->latest()->paginate(20);
        $likesCount = $submission->likes()->count();
        $liked = auth()->user()?->coloringWorkLikes()->where('coloring_submission_id', $submission->id)->exists() ?? false;
        return view('colorings.gallery-work', compact('submission', 'comments', 'likesCount', 'liked'));
    }

    private function privateImage(ColoringSubmission $submission)
    {
        abort_unless(preg_match('~^coloring-submissions/[A-Za-z0-9._/-]+\.png$~i', $submission->file_path) === 1, 404);
        $path = \Illuminate\Support\Facades\Storage::disk('local')->path($submission->file_path);
        abort_unless(is_file($path), 404);
        return response()->file($path, ['Content-Type' => 'image/png', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=3600']);
    }
}
