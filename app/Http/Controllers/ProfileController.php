<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use App\Models\SocialAccount;
use App\Http\Controllers\Concerns\RespondsToAjax;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    use RespondsToAjax;

    public function show(Request $request): View
    {
        return $this->page($request, 'overview');
    }

    public function history(Request $request): View
    {
        return $this->page($request, 'history');
    }

    public function settings(Request $request): View
    {
        return $this->page($request, 'settings');
    }

    public function security(Request $request): View
    {
        return $this->page($request, 'security');
    }

    private function page(Request $request, string $section): View
    {
        $user = $request->user();
        $history = $section === 'history' ? $user->readingHistories()->with('story')->whereHas('story', fn ($query) => $query->published())->latest('last_read_at')->paginate(12) : null;
        $quizAttempts = $section === 'history' ? $user->storyQuizAttempts()->with('story')->whereHas('story', fn ($query) => $query->published())->latest()->limit(50)->get() : collect();
        $gameAttempts = $section === 'history' ? $user->interactiveGameAttempts()->with('game')->whereHas('game', fn ($query) => $query->published())->latest('completed_at')->limit(50)->get() : collect();
        $coloringFavorites = $section === 'history' ? $user->coloringFavorites()->with('page.category')->whereHas('page', fn ($query) => $query->where('is_published', true)->whereHas('category', fn ($category) => $category->where('is_published', true)))->latest()->limit(50)->get() : collect();
        $coloringProgress = $section === 'history' ? $user->coloringProgress()->with('page.category')->whereHas('page', fn ($query) => $query->where('is_published', true)->whereHas('category', fn ($category) => $category->where('is_published', true)))->latest()->limit(50)->get() : collect();
        $coloringSubmissions = $section === 'history' ? $user->coloringSubmissions()->with('page')->latest()->limit(50)->get() : collect();
        $audioProgress = in_array($section, ['overview', 'history'], true) ? $user->audioPlaybackStates()->with('story')->whereHas('story', fn ($query) => $query->published()->whereNotNull('audio_path'))->where('position_seconds', '>', 0)->latest('updated_at')->limit($section === 'overview' ? 3 : 50)->get() : collect();
        $savedAudioQueue = in_array($section, ['overview', 'history'], true) ? $user->audioPlaybackStates()->with('story')->whereNotNull('queue_position')->whereHas('story', fn ($query) => $query->published()->whereNotNull('audio_path'))->orderBy('queue_position')->limit($section === 'overview' ? 3 : 50)->get() : collect();
        $avatarUrl = $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null;
        $preferences = array_merge(['reader_font_size' => 20, 'reader_theme' => 'cream', 'reader_width' => 'medium'], $user->profile_preferences ?? []);
        $newsletterConsent = in_array($section, ['overview', 'settings'], true) ? $user->consentEvents()->where('consent_type', 'newsletter')->orderByDesc('recorded_at')->orderByDesc('id')->first() : null;
        $consentEvents = in_array($section, ['overview', 'settings'], true) ? $user->consentEvents()->whereIn('consent_type', ['terms', 'privacy_notice'])->orderBy('recorded_at')->get()->keyBy('consent_type') : collect();
        $socialAccounts = in_array($section, ['overview', 'security'], true) ? $user->socialAccounts()->orderBy('provider')->get() : collect();
        $achievements = [];
        if ($section === 'overview') {
            $publishedReads = $user->readingHistories()
                ->whereHas('story', fn ($query) => $query->published())
                ->distinct()
                ->count('story_id');
            $publishedQuizzes = $user->storyQuizAttempts()
                ->whereHas('story', fn ($query) => $query->published())
                ->count();
            $publishedGames = $user->interactiveGameAttempts()
                ->whereHas('game', fn ($query) => $query->published())
                ->count();
            $publishedColoringProgress = $user->coloringProgress()
                ->whereHas('page', fn ($page) => $page->where('is_published', true)
                    ->whereHas('category', fn ($category) => $category->where('is_published', true)))
                ->count();

            $achievements = [
                ['icon' => 'book', 'title' => 'Первое чтение', 'description' => 'Откройте первую сказку', 'progress' => $publishedReads, 'target' => 1, 'url' => route('home')],
                ['icon' => 'star', 'title' => 'Любознательный читатель', 'description' => 'Откройте пять разных сказок', 'progress' => $publishedReads, 'target' => 5, 'url' => route('profile.history')],
                ['icon' => 'puzzle', 'title' => 'Первый ответ', 'description' => 'Пройдите викторину по сказке', 'progress' => $publishedQuizzes, 'target' => 1, 'url' => route('games.index')],
                ['icon' => 'puzzle', 'title' => 'Первая игра', 'description' => 'Соберите мозаику или найдите отличия', 'progress' => $publishedGames, 'target' => 1, 'url' => route('games.index')],
                ['icon' => 'palette', 'title' => 'Начинающий художник', 'description' => 'Сохраните прогресс раскрашивания', 'progress' => $publishedColoringProgress, 'target' => 1, 'url' => route('colorings.index')],
                ['icon' => 'image', 'title' => 'В галерее', 'description' => 'Поделитесь работой после проверки', 'progress' => $user->coloringSubmissions()->where('status', 'approved')->count(), 'target' => 1, 'url' => route('colorings.gallery')],
            ];
        }

        return view('profile.show', compact('section', 'user', 'history', 'quizAttempts', 'gameAttempts', 'coloringFavorites', 'coloringProgress', 'coloringSubmissions', 'audioProgress', 'savedAudioQueue', 'avatarUrl', 'preferences', 'newsletterConsent', 'consentEvents', 'socialAccounts', 'achievements'));
    }

    public function exportData(Request $request): StreamedResponse
    {
        $user = $request->user();

        $export = [
            'schema_version' => 1,
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'created_at' => $user->created_at?->toIso8601String(),
                'profile_preferences' => $user->profile_preferences ?? [],
            ],
            'consents' => $user->consentEvents()->orderBy('recorded_at')->get(['consent_type', 'policy_version', 'action', 'source', 'recorded_at'])->toArray(),
            'reading_history' => $user->readingHistories()->with('story:id,title,slug')->latest('last_read_at')->get()->map(fn ($entry) => [
                'story' => $entry->story ? ['title' => $entry->story->title, 'slug' => $entry->story->slug] : null,
                'last_read_at' => $entry->last_read_at?->toIso8601String(),
                'paragraph_position' => $entry->paragraph_position,
            ])->all(),
            'story_quiz_attempts' => $user->storyQuizAttempts()->with('story:id,title,slug')->latest()->get()->map(fn ($attempt) => [
                'story' => $attempt->story ? ['title' => $attempt->story->title, 'slug' => $attempt->story->slug] : null,
                'score' => $attempt->score,
                'question_count' => $attempt->question_count,
                'answers' => $attempt->answers,
                'created_at' => $attempt->created_at?->toIso8601String(),
            ])->all(),
            'interactive_game_attempts' => $user->interactiveGameAttempts()->with('game:id,title,type')->latest('completed_at')->get()->map(fn ($attempt) => [
                'game' => $attempt->game ? ['title' => $attempt->game->title, 'type' => $attempt->game->type] : null,
                'duration_seconds' => $attempt->duration_seconds,
                'completed_at' => $attempt->completed_at?->toIso8601String(),
            ])->all(),
            'audio_playback' => $user->audioPlaybackStates()->with('story:id,title,slug')->get()->map(fn ($state) => [
                'story' => $state->story ? ['title' => $state->story->title, 'slug' => $state->story->slug] : null,
                'position_seconds' => $state->position_seconds,
                'queue_position' => $state->queue_position,
                'updated_at' => $state->updated_at?->toIso8601String(),
            ])->all(),
            'coloring_favorites' => $user->coloringFavorites()->with('page:id,title,slug,coloring_category_id', 'page.category:id,name,slug')->get()->map(fn ($favorite) => [
                'page' => $favorite->page ? ['title' => $favorite->page->title, 'slug' => $favorite->page->slug, 'category' => $favorite->page->category?->name] : null,
                'created_at' => $favorite->created_at?->toIso8601String(),
            ])->all(),
            'coloring_progress' => $user->coloringProgress()->with('page:id,title,slug,coloring_category_id', 'page.category:id,name,slug')->get()->map(fn ($progress) => [
                'page' => $progress->page ? ['title' => $progress->page->title, 'slug' => $progress->page->slug, 'category' => $progress->page->category?->name] : null,
                'strokes' => $progress->strokes,
                'updated_at' => $progress->updated_at?->toIso8601String(),
            ])->all(),
            'coloring_submissions' => $user->coloringSubmissions()->with('page:id,title')->latest()->get()->map(fn ($submission) => [
                'title' => $submission->title,
                'page_title' => $submission->page?->title,
                'status' => $submission->status,
                'guardian_consent_at' => $submission->guardian_consent_at?->toIso8601String(),
                'created_at' => $submission->created_at?->toIso8601String(),
            ])->all(),
            'connected_accounts' => $user->socialAccounts()->get(['provider', 'email', 'display_name', 'created_at'])->toArray(),
        ];

        return response()->streamDownload(
            fn () => print json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'skazkomir-data-'.$user->id.'-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:80']]);
        $request->user()->update(['name' => trim($data['name'])]);
        return $this->respond($request, 'Имя в профиле обновлено.');
    }

    public function updatePassword(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => [$user->password_login_enabled ? 'required' : 'nullable', ...($user->password_login_enabled ? ['current_password'] : [])],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        $user->forceFill(['password' => Hash::make($data['password']), 'password_login_enabled' => true, 'oauth_provider' => null, 'oauth_id' => null, 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return $this->respond($request, 'Пароль изменён. Для входа на других устройствах используйте новый пароль.');
    }

    public function updateAvatar(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2000,max_height=2000'],
        ]);

        $user = $request->user();
        $oldPath = $user->avatar_path;
        $path = $data['avatar']->store('avatars', 'public');
        if (! $path) {
            throw ValidationException::withMessages(['avatar' => 'Не удалось сохранить изображение. Попробуйте ещё раз.']);
        }

        $user->forceFill(['avatar_path' => $path])->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $this->respond($request, 'Фото профиля обновлено.');
    }

    public function removeAvatar(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        return $this->respond($request, 'Фото профиля удалено.');
    }

    public function updatePreferences(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'reader_font_size' => ['required', 'integer', 'in:16,18,20,22,24'],
            'reader_theme' => ['required', 'in:cream,white,green'],
            'reader_width' => ['required', 'in:narrow,medium,wide'],
        ]);

        $request->user()->forceFill(['profile_preferences' => $data])->save();

        return $this->respond($request, 'Настройки чтения сохранены.');
    }

    public function updateNewsletterConsent(Request $request): RedirectResponse|JsonResponse
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

        return $this->respond($request, $wantsNewsletter ? 'Согласие на новости сохранено.' : 'Вы больше не будете получать новости проекта.');
    }

    public function removeSocialAccount(Request $request, SocialAccount $socialAccount): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        abort_unless($socialAccount->user_id === $user->id, 404);

        $hasAnotherProvider = $user->socialAccounts()->where('id', '<>', $socialAccount->id)->exists();
        if (! $user->password_login_enabled && ! $hasAnotherProvider) {
            throw ValidationException::withMessages(['oauth' => 'Нельзя отвязать последний способ входа. Сначала задайте пароль или привяжите другую сеть.']);
        }

        if ($user->oauth_provider === $socialAccount->provider && $user->oauth_id === $socialAccount->provider_user_id) {
            $user->forceFill(['oauth_provider' => null, 'oauth_id' => null])->save();
        }

        $socialAccount->delete();

        return $this->respond($request, 'Связь с аккаунтом удалена.');
    }
}
