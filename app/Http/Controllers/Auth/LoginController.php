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

    public function create(): View { return view('auth.login'); }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email', 'max:254'], 'password' => ['required', 'string', 'max:200']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) throw ValidationException::withMessages(['email' => 'Проверьте адрес почты и пароль.']);
        if ($request->user()->isBlocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages(['email' => 'Доступ к аккаунту временно ограничен. Обратитесь в поддержку сайта.']);
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
