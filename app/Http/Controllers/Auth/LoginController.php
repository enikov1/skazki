<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use RespondsToAjax;

    public function create(Request $request): View
    {
        $target = $request->query('redirect');
        if (is_string($target)) {
            $parts = parse_url($target);
            $relative = str_starts_with($target, '/') && ! str_starts_with($target, '//') && ! str_contains($target, '\\');
            $sameHost = is_array($parts) && ($parts['host'] ?? null) === $request->getHost()
                && in_array($parts['scheme'] ?? '', ['https', 'http'], true) && ! isset($parts['user']) && ! isset($parts['pass']);
            if (($relative || $sameHost) && ! preg_match('/[\x00-\x20]/', $target)) $request->session()->put('url.intended', $target);
        }
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (is_string($request->input('email'))) $request->merge(['email' => \Illuminate\Support\Str::lower(trim($request->input('email')))]);
        $credentials = $request->validate(['email' => ['required', 'email', 'max:254'], 'password' => ['required', 'string', 'max:200']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $message = 'Проверьте адрес почты и пароль.';
            if ($request->expectsJson()) throw ValidationException::withMessages(['email' => $message]);

            return back()->withErrors(['email' => $message])->onlyInput('email');
        }
        if ($request->user()->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Доступ к аккаунту временно ограничен. Обратитесь в поддержку сайта.';
            if ($request->expectsJson()) throw ValidationException::withMessages(['email' => $message]);

            return redirect()->route('login')->withErrors(['email' => $message]);
        }
        $request->session()->regenerate();

        return $this->respond($request, 'Вы вошли в аккаунт.', redirect()->intended(route('home'))->getTargetUrl());
    }

    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken();
        return $this->respond($request, 'Вы вышли из аккаунта.', route('home'));
    }
}
