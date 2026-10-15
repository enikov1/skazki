<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $history = $user->readingHistories()->with('story')->whereHas('story', fn ($query) => $query->published())->latest('last_read_at')->paginate(8);

        return view('profile.show', compact('user', 'history'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:80']]);
        $request->user()->update(['name' => trim($data['name'])]);
        return back()->with('status', 'Имя в профиле обновлено.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()],
        ]);

        $user = $request->user();
        $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return back()->with('status', 'Пароль изменён. Для входа на других устройствах используйте новый пароль.');
    }
}
