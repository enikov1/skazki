<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'genre' => ['nullable', 'string', 'max:80'],
            'age' => ['nullable', 'in:3-5,5-8,9-12'],
        ]);
        $query = Story::published()->whereHas('quizQuestions')
            ->when(filled($filters['q'] ?? null), fn ($stories) => $stories->where(fn ($search) => $search->where('title', 'like', '%'.$filters['q'].'%')->orWhere('summary', 'like', '%'.$filters['q'].'%')))
            ->when(filled($filters['genre'] ?? null), fn ($stories) => $stories->where('genre', $filters['genre']))
            ->when(filled($filters['age'] ?? null), function ($stories) use ($filters): void {
                [$min, $max] = array_map('intval', explode('-', $filters['age']));
                $stories->where('age_min', '<=', $max)->where('age_max', '>=', $min);
            })
            ->withCount('quizQuestions')->orderBy('title');
        $stories = $query->paginate(12)->withQueryString();
        $genres = Story::published()->whereHas('quizQuestions')->select('genre')->distinct()->orderBy('genre')->pluck('genre');
        if ($request->expectsJson()) {
            return response()->json(['html' => view('games.results', compact('stories'))->render(), 'count' => $stories->total()]);
        }
        return view('games.index', compact('stories', 'genres', 'filters'));
    }
}
