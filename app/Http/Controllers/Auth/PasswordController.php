<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    use RespondsToAjax;

    public function requestForm(): View { return view('auth.forgot-password'); }

    public function sendLink(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:254']]);
        Password::sendResetLink(['email' => Str::lower(trim((string) $request->input('email')))]);
        return $this->respond($request, 'Если аккаунт с таким адресом существует, мы отправили инструкцию по восстановлению пароля. Проверьте почту и папку «Спам».');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse|JsonResponse
    {
        if (is_string($request->input('email'))) $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        $data = $request->validate(['token' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:254'], 'password' => ['required', 'string', 'max:200', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()],]);
        $status = Password::reset($data, function ($user, string $password): void {
            $user->forceFill(['password' => Hash::make($password), 'password_login_enabled' => true, 'oauth_provider' => null, 'oauth_id' => null, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) throw ValidationException::withMessages(['email' => __($status)]);

        return $this->respond($request, __($status), route('login'));
    }
}
