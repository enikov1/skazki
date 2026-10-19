<?php

namespace App\Providers;

use App\Models\Story;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\AuditLog;
use App\Models\InteractiveGame;
use App\Models\Comment;
use App\Models\User;
use App\Policies\ArticleCommentPolicy;
use App\Policies\ArticlePolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\InteractiveGamePolicy;
use App\Policies\CommentPolicy;
use App\Policies\StoryPolicy;
use App\Policies\UserPolicy;
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
        if (app()->environment('production') && str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        Gate::policy(Story::class, StoryPolicy::class);
        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
        Gate::policy(ArticleComment::class, ArticleCommentPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(InteractiveGame::class, InteractiveGamePolicy::class);
        Gate::before(fn ($user) => $user->hasRole('admin') ? true : null);

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(30)->by('login-ip:'.$request->ip()),
            Limit::perMinute(5)->by('login-account:'.(is_string($request->input('email')) ? strtolower(trim($request->input('email'))) : '').'|'.$request->ip()),
        ]);
        RateLimiter::for('public-write', fn (Request $request) => [
            Limit::perMinute(60)->by('write-ip:'.$request->ip()),
            Limit::perMinute(8)->by('write-action:'.($request->user()?->id ?? $request->ip()).'|'.$request->path()),
        ]);
        Event::listen(\SocialiteProviders\Manager\SocialiteWasCalled::class, function ($event): void {
            $event->extendSocialite('yandex', \SocialiteProviders\Yandex\Provider::class);
            $event->extendSocialite('vkontakte', \SocialiteProviders\VKontakte\Provider::class);
        });
    }
}
