<?php

use App\Http\Middleware\RequireRole;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\RedirectOldContentSlugs;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO);
        $middleware->append(AddSecurityHeaders::class);
        $middleware->alias(['role' => RequireRole::class, 'account.active' => EnsureAccountActive::class, 'canonical.slug' => RedirectOldContentSlugs::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Report production exceptions to the configured Laravel log channel.
    })->create();
