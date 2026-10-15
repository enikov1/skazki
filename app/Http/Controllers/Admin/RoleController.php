<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::with('roles:id,name,slug')->latest()->paginate(30), 'roles' => Role::orderBy('id')->get(['id', 'name', 'slug'])]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(['user', 'editor', 'moderator', 'admin'])]]);
        $currentRole = $user->roles()->value('slug');
        if ($request->user()->is($user) && $data['role'] !== 'admin') return back()->withErrors(['role' => 'Нельзя убрать роль администратора у своей учётной записи.']);
        if ($currentRole === 'admin' && $data['role'] !== 'admin') {
            $admins = Role::where('slug', 'admin')->firstOrFail()->users()->count();
            if ($admins <= 1) return back()->withErrors(['role' => 'В системе должен остаться хотя бы один администратор.']);
        }
        DB::transaction(fn () => $user->roles()->sync([Role::where('slug', $data['role'])->value('id')]));
        return back()->with('status', 'Роль пользователя обновлена.');
    }
}
