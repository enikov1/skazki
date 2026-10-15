<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $history = $user->readingHistories()->with('story')->whereHas('story', fn ($query) => $query->published())->latest('last_read_at')->paginate(8);
        $avatarUrl = $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null;
        $preferences = array_merge(['reader_font_size' => 20, 'reader_theme' => 'cream', 'reader_width' => 'medium'], $user->profile_preferences ?? []);
        $newsletterConsent = $user->consentEvents()->where('consent_type', 'newsletter')->orderByDesc('recorded_at')->orderByDesc('id')->first();
        $consentEvents = $user->consentEvents()->whereIn('consent_type', ['terms', 'privacy_notice'])->orderBy('recorded_at')->get()->keyBy('consent_type');

        return view('profile.show', compact('user', 'history', 'avatarUrl', 'preferences', 'newsletterConsent', 'consentEvents'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:80']]);
        $request->user()->update(['name' => trim($data['name'])]);
        return back()->with('status', 'Имя в профиле обновлено.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return back()->with('status', 'Пароль изменён. Для входа на других устройствах используйте новый пароль.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ]);

        $user = $request->user();
        $oldPath = $user->avatar_path;
        $path = $data['avatar']->store('avatars', 'public');
        if (! $path) {
            return back()->withErrors(['avatar' => 'Не удалось сохранить изображение. Попробуйте ещё раз.']);
        }

        $user->forceFill(['avatar_path' => $path])->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('status', 'Фото профиля обновлено.');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return back()->with('status', 'Фото профиля удалено.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reader_font_size' => ['required', 'integer', 'in:16,18,20,22,24'],
            'reader_theme' => ['required', 'in:cream,white,green'],
            'reader_width' => ['required', 'in:narrow,medium,wide'],
        ]);

        $request->user()->forceFill(['profile_preferences' => $data])->save();

        return back()->with('status', 'Настройки чтения сохранены.');
    }

    public function updateNewsletterConsent(Request $request): RedirectResponse
    {
        $data = $request->validate(['newsletter' => ['required', 'boolean']]);
        $user = $request->user();
        $latest = $user->consentEvents()->where('consent_type', 'newsletter')->orderByDesc('recorded_at')->orderByDesc('id')->first();
        $wantsNewsletter = (bool) $data['newsletter'];
        $currentlySubscribed = $latest?->action === 'granted';

        if ($wantsNewsletter !== $currentlySubscribed) {
            $user->consentEvents()->create([
                'consent_type' => 'newsletter',
                'policy_version' => null,
                'action' => $wantsNewsletter ? 'granted' : 'revoked',
                'source' => 'profile',
                'recorded_at' => now(),
            ]);
        }

        return back()->with('status', $wantsNewsletter ? 'Согласие на новости сохранено.' : 'Вы больше не будете получать новости проекта.');
    }
}
