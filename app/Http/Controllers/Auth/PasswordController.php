<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function requestForm(): View { return view('auth.forgot-password'); }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:254']]);
        $status = Password::sendResetLink(['email' => Str::lower(trim((string) $request->input('email')))]);
        return back()->with('status', __($status));
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email', 'max:254'], 'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()],]);
        $status = Password::reset($data, function ($user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'password_login_enabled' => true, 'oauth_provider' => null, 'oauth_id' => null, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', __($status)) : back()->withErrors(['email' => __($status)]);
    }
}
