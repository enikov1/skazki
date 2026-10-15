<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
    use RespondsToAjax;
    public function index(Request $request): View
    {
        Gate::authorize('create', Story::class);

        $media = collect();
        foreach (['covers', 'illustrations'] as $directory) {
            $absoluteDirectory = public_path('assets/'.$directory);
            if (! File::isDirectory($absoluteDirectory)) {
                continue;
            }

            foreach (File::files($absoluteDirectory) as $file) {
                if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    $media->push(['path' => 'assets/'.$directory.'/'.$file->getFilename(), 'name' => $file->getFilename()]);
                }
            }
        }

        foreach (Storage::disk('public')->files('media') as $path) {
            if (in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $media->push(['path' => 'storage/'.$path, 'name' => basename($path)]);
            }
        }

        $media = $media->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
        $audio = collect(Storage::disk('local')->files('audio'))
            ->filter(fn (string $path): bool => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'mp3')
            ->map(fn (string $path): array => ['path' => $path, 'name' => basename($path), 'size' => Storage::disk('local')->size($path)])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        return view('admin.media.index', [
            'media' => $media,
            'audio' => $audio,
            'select' => $request->query('select') === 'cover_path' ? 'cover_path' : null,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', Story::class);

        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $path = $data['image']->storePublicly('media', 'public');
        if (! $path) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Не удалось сохранить изображение. Проверьте свободное место и настройки диска.', 'errors' => ['image' => ['Не удалось сохранить изображение. Проверьте свободное место и настройки диска.']]], 422);
            }
            return back()->withErrors(['image' => 'Не удалось сохранить изображение. Проверьте свободное место и настройки диска.']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Изображение загружено. Выберите его или скопируйте путь.',
                'media' => ['path' => 'storage/'.$path, 'name' => basename($path), 'url' => Storage::disk('public')->url($path)],
            ]);
        }

        return redirect()->route('admin.media.index', $request->only('select'))
            ->with('status', 'Изображение загружено. Выберите его или скопируйте путь.');
    }

    public function storeAudio(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', Story::class);
        $data = $request->validate([
            'audio' => ['required', 'file', 'mimes:mp3', 'mimetypes:audio/mpeg,audio/mp3,audio/x-mpeg', 'max:51200'],
        ]);
        $path = $data['audio']->store('audio', 'local');
        if (! $path) {
            $message = 'Не удалось сохранить аудиофайл. Проверьте свободное место и настройки диска.';
            return $request->expectsJson()
                ? response()->json(['message' => $message, 'errors' => ['audio' => [$message]]], 422)
                : back()->withErrors(['audio' => $message]);
        }

        $item = [
            'path' => $path,
            'name' => basename($path),
            'size' => Storage::disk('local')->size($path),
            'url' => route('admin.audio.preview', ['filename' => basename($path)]),
        ];
        if ($request->expectsJson()) {
            return response()->json(['message' => 'MP3 загружен в приватное хранилище.', 'audio' => $item]);
        }
        return back()->with('status', 'MP3 загружен в приватное хранилище.');
    }

    public function previewAudio(string $filename)
    {
        $path = 'audio/'.$filename;
        abort_unless(strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'mp3' && Storage::disk('local')->exists($path), 404);
        return response()->file(Storage::disk('local')->path($path), ['Content-Type' => 'audio/mpeg', 'X-Content-Type-Options' => 'nosniff']);
    }
}
