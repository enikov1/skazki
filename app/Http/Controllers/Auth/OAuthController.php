<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Socialite;
use Illuminate\Support\Str;
use Throwable;

class OAuthController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['yandex', 'vkontakte'], true), 404);
        abort_if(blank(config("services.$provider.client_id")) || blank(config("services.$provider.client_secret")), 503, 'Вход через эту сеть пока не настроен.');
        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, ['yandex', 'vkontakte'], true), 404);
        try {
            $external = Socialite::driver($provider)->user();
            $user = DB::transaction(function () use ($provider, $external): User {
                $user = User::where('oauth_provider', $provider)->where('oauth_id', $external->getId())->first();
                if ($user) return $user;

                // Never silently link an OAuth identity to an existing account by email.
                abort_if(blank($external->getEmail()), 422, 'Провайдер не передал адрес электронной почты. Войдите по почте или настройте выдачу email для приложения.');
                abort_if(User::where('email', strtolower($external->getEmail()))->exists(), 409, 'Аккаунт с этой почтой уже есть. Сначала войдите в него по паролю.');
                $user = User::create(['name' => Str::limit($external->getName() ?: 'Читатель', 120, ''), 'email' => strtolower($external->getEmail()), 'password' => Hash::make(Str::random(64)), 'oauth_provider' => $provider, 'oauth_id' => (string) $external->getId(), 'email_verified_at' => now()]);
                $user->roles()->attach(Role::where('slug', 'user')->value('id'));
                return $user;
            });
            auth()->login($user, true);
            request()->session()->regenerate();
            return redirect()->intended(route('home'));
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('login')->withErrors(['oauth' => 'Не удалось войти. Попробуйте ещё раз или воспользуйтесь почтой и паролем.']);
        }
    }
}
