<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Throwable;

class OAuthController extends Controller
{
    private const PROVIDERS = ['yandex', 'vkontakte'];

    public function redirect(string $provider, Request $request): RedirectResponse
    {
        $request->session()->forget('oauth_link_provider');

        return $this->start($provider);
    }

    public function link(string $provider, Request $request): RedirectResponse
    {
        $result = $this->configurationError($provider);
        if ($result) {
            return $result;
        }

        $request->session()->put('oauth_link_provider', $provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, Request $request): RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        $linkingProvider = $request->session()->pull('oauth_link_provider');

        try {
            $external = Socialite::driver($provider)->user();
        } catch (Throwable $exception) {
            report($exception);

            return $this->oauthError($request, 'Не удалось связаться с сервисом входа. Попробуйте ещё раз.');
        }

        $externalId = trim((string) $external->getId());
        if ($externalId === '') {
            return $this->oauthError($request, 'Сервис входа не передал идентификатор аккаунта. Попробуйте другой способ.');
        }

        if ($linkingProvider !== null) {
            return $this->linkAccount($request, $provider, $linkingProvider, $external, $externalId);
        }

        $account = SocialAccount::with('user')->where('provider', $provider)->where('provider_user_id', $externalId)->first();
        if ($account) {
            auth()->login($account->user, true);
            $request->session()->regenerate();

            return redirect()->intended(route('home'));
        }

        $email = Str::lower(trim((string) $external->getEmail()));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->oauthError($request, 'Сервис не передал почту. Войдите по email и паролю, затем привяжите аккаунт в профиле.');
        }

        if (User::where('email', $email)->exists()) {
            return $this->oauthError($request, 'Войдите в свой аккаунт по email, затем привяжите Яндекс или VK в профиле.');
        }

        try {
            $user = DB::transaction(function () use ($provider, $external, $externalId, $email): User {
                $user = User::create([
                    'name' => Str::limit($external->getName() ?: 'Читатель', 120, ''),
                    'email' => $email,
                    'password' => Hash::make(Str::random(64)),
                    'oauth_provider' => $provider,
                    'oauth_id' => $externalId,
                    'email_verified_at' => now(),
                    'password_login_enabled' => false,
                ]);
                $user->roles()->attach(Role::where('slug', 'user')->value('id'));
                $user->socialAccounts()->create([
                    'provider' => $provider,
                    'provider_user_id' => $externalId,
                    'email' => $email,
                    'display_name' => Str::limit($external->getName() ?: '', 120, ''),
                ]);

                return $user;
            });
        } catch (QueryException $exception) {
            report($exception);

            return $this->oauthError($request, 'Не удалось создать аккаунт. Войдите в существующий профиль и привяжите эту сеть там.');
        }

        auth()->login($user, true);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Добро пожаловать в СказкоМир!');
    }

    private function linkAccount(Request $request, string $provider, string $linkingProvider, object $external, string $externalId): RedirectResponse
    {
        if ($linkingProvider !== $provider) {
            return $this->oauthError($request, 'Не получилось привязать эту сеть. Начните привязку ещё раз из профиля.');
        }

        $user = $request->user();
        if (! $user) {
            return $this->oauthError($request, 'Сначала войдите в нужный аккаунт СказкоМира и начните привязку из профиля.');
        }

        $account = SocialAccount::where('provider', $provider)->where('provider_user_id', $externalId)->first();
        if ($account && $account->user_id !== $user->id) {
            return $this->oauthError($request, 'Этот аккаунт уже привязан к другому профилю.');
        }

        if ($account) {
            return redirect()->route('profile.show')->with('status', 'Этот аккаунт уже привязан к вашему профилю.');
        }

        if ($user->socialAccounts()->where('provider', $provider)->exists()) {
            return redirect()->route('profile.show')->withErrors(['oauth' => 'Сначала отвяжите текущий аккаунт этой сети.']);
        }

        try {
            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_user_id' => $externalId,
                'email' => filled($external->getEmail()) ? Str::lower(trim((string) $external->getEmail())) : null,
                'display_name' => Str::limit($external->getName() ?: '', 120, ''),
            ]);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('profile.show')->withErrors(['oauth' => 'Не удалось привязать аккаунт. Обновите страницу и попробуйте ещё раз.']);
        }

        return redirect()->route('profile.show')->with('status', 'Аккаунт привязан. Теперь можно входить через эту сеть.');
    }

    private function start(string $provider): RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        $result = $this->configurationError($provider);
        if ($result) {
            return $result;
        }

        return Socialite::driver($provider)->redirect();
    }

    private function configurationError(string $provider): ?RedirectResponse
    {
        if (! in_array($provider, self::PROVIDERS, true)) {
            abort(404);
        }

        if (blank(config("services.$provider.client_id")) || blank(config("services.$provider.client_secret"))) {
            $destination = auth()->check() ? route('profile.show') : route('login');

            return redirect($destination)->withErrors(['oauth' => 'Вход через эту сеть пока не настроен.']);
        }

        return null;
    }

    private function oauthError(Request $request, string $message): RedirectResponse
    {
        $destination = $request->user() ? route('profile.show') : route('login');

        return redirect($destination)->withErrors(['oauth' => $message]);
    }
}
