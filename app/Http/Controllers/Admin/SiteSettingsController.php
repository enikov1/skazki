<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SiteSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.branding', [
            'logoPath' => SiteSetting::query()->where('key', 'print_watermark_logo')->value('value'),
            'logoUrl' => SiteSetting::printLogoUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'print_watermark_logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ], [
            'print_watermark_logo.required' => 'Выберите файл логотипа.',
            'print_watermark_logo.image' => 'Загрузите файл изображения.',
            'print_watermark_logo.mimes' => 'Поддерживаются PNG, JPG и WebP.',
            'print_watermark_logo.max' => 'Размер файла не должен превышать 4 МБ.',
        ]);

        $oldPath = SiteSetting::query()->where('key', 'print_watermark_logo')->value('value');
        $newPath = $data['print_watermark_logo']->store('branding', 'public');
        SiteSetting::query()->updateOrCreate(['key' => 'print_watermark_logo'], ['value' => $newPath]);

        if ($oldPath && str_starts_with($oldPath, 'branding/')) {
            Storage::disk('public')->delete($oldPath);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Логотип для печати обновлён.', 'logo_url' => Storage::disk('public')->url($newPath)]);
        }

        return back()->with('status', 'Логотип для печати обновлён.');
    }
}
