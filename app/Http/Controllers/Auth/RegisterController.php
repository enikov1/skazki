<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    use RespondsToAjax;

    public function create(): View { return view('auth.register'); }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        if (is_string($request->input('email'))) $request->merge(['email' => Str::lower(trim($request->input('email')))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:254', 'unique:users,email'], 'password' => ['required', 'string', 'min:10', 'confirmed'], 'terms' => ['accepted'], 'newsletter' => ['sometimes', 'boolean']]);
        $user = DB::transaction(function () use ($data, $request): User {
            $user = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => Hash::make($data['password'])]);
            $user->roles()->attach(Role::where('slug', 'user')->value('id'));

            foreach (['terms', 'privacy_notice'] as $consentType) {
                $user->consentEvents()->create(['consent_type' => $consentType, 'policy_version' => 'v1', 'action' => 'granted', 'source' => 'registration', 'recorded_at' => now()]);
            }

            if ($request->boolean('newsletter')) {
                $user->consentEvents()->create(['consent_type' => 'newsletter', 'policy_version' => null, 'action' => 'granted', 'source' => 'registration', 'recorded_at' => now()]);
            }

            return $user;
        });
        event(new Registered($user));
        auth()->login($user);
        $request->session()->regenerate();

        return $this->respond($request, 'Рады видеть вас в СказкоМире!', route('home'));
    }
}
