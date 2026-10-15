<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaLibraryController extends Controller
{
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

        return view('admin.media.index', [
            'media' => $media,
            'select' => $request->query('select') === 'cover_path' ? 'cover_path' : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Story::class);

        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ]);

        $path = $data['image']->storePublicly('media', 'public');
        if (! $path) {
            return back()->withErrors(['image' => 'Не удалось сохранить изображение. Проверьте свободное место и настройки диска.']);
        }

        return redirect()->route('admin.media.index', $request->only('select'))
            ->with('status', 'Изображение загружено. Выберите его или скопируйте путь.');
    }
}
