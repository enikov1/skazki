<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View { return view('auth.register'); }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:254', 'unique:users,email'], 'password' => ['required', 'string', 'min:10', 'confirmed'], 'terms' => ['accepted']]);
        $user = DB::transaction(function () use ($data): User {
            $user = User::create(['name' => $data['name'], 'email' => strtolower($data['email']), 'password' => Hash::make($data['password'])]);
            $user->roles()->attach(Role::where('slug', 'user')->value('id'));
            return $user;
        });
        event(new Registered($user));
        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('status', 'Рады видеть вас в СказкоМире!');
    }
}
