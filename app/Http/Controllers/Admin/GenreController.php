<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GenreController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $genres = Genre::query()->withCount('stories')->orderBy('name')->get();
        return view('admin.genres.index', compact('genres'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'unique:genres,name']]);
        $genre = Genre::create($data);
        Cache::forget('stories:genres:v1');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Жанр добавлен.',
                'genre' => ['id' => $genre->id, 'name' => $genre->name, 'update_url' => route('admin.genres.update', $genre), 'delete_url' => route('admin.genres.destroy', $genre)],
            ]);
        }
        return $this->respond($request, 'Жанр добавлен.');
    }

    public function update(Request $request, Genre $genre): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', Rule::unique('genres', 'name')->ignore($genre->id)]]);
        $oldName = $genre->name;
        $genre->update($data);

        Story::query()->where('genre', $oldName)->update(['genre' => $genre->name]);
        Cache::forget('stories:genres:v1');
        Story::query()->where('genre', $genre->name)->pluck('slug')->each(fn (string $slug) => Cache::forget('stories:detail:v1:'.$slug));

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Название жанра обновлено.', 'genre' => ['id' => $genre->id, 'name' => $genre->name]]);
        }
        return $this->respond($request, 'Название жанра обновлено.');
    }

    public function destroy(Request $request, Genre $genre): JsonResponse|RedirectResponse
    {
        if (Story::query()->where('genre', $genre->name)->exists()) {
            $message = 'Нельзя удалить жанр, пока он используется в сказках. Сначала переназначьте материалы.';
            return $request->expectsJson()
                ? response()->json(['message' => $message, 'errors' => ['genre' => [$message]]], 422)
                : back()->withErrors(['genre' => $message]);
        }

        $genre->delete();
        Cache::forget('stories:genres:v1');
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Жанр удалён.']);
        }
        return $this->respond($request, 'Жанр удалён.');
    }
}
