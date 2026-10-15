<?php

namespace App\Providers;

use App\Models\Story;
use App\Policies\StoryPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Story::class, StoryPolicy::class);
        Gate::before(fn ($user) => $user->hasRole('admin') ? true : null);

        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('public-write', fn (Request $request) => Limit::perMinute(8)->by(($request->user()?->id ?? $request->ip()).'|'.$request->path()));
        Event::listen(\SocialiteProviders\Manager\SocialiteWasCalled::class, function ($event): void {
            $event->extendSocialite('yandex', \SocialiteProviders\Yandex\Provider::class);
            $event->extendSocialite('vkontakte', \SocialiteProviders\VKontakte\Provider::class);
        });
    }
}
