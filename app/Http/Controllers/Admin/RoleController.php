<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['user', 'editor', 'moderator', 'admin'])],
            'status' => ['nullable', Rule::in(['active', 'blocked'])],
        ]);
        $users = User::with(['roles:id,name,slug', 'blocker:id,name'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where(fn (Builder $search) => $search->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->whereHas('roles', fn (Builder $roles) => $roles->where('slug', $role)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $status === 'blocked' ? $query->whereNotNull('blocked_at') : $query->whereNull('blocked_at'))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::orderBy('id')->get(['id', 'name', 'slug']), 'filters' => $filters]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::in(['user', 'editor', 'moderator', 'admin'])]]);
        $currentRole = $user->roles()->value('slug');
        if ($request->user()->is($user) && $data['role'] !== 'admin') return back()->withErrors(['role' => 'Нельзя убрать роль администратора у своей учётной записи.']);
        if ($currentRole === 'admin' && $data['role'] !== 'admin') {
            $admins = Role::where('slug', 'admin')->firstOrFail()->users()->whereNull('blocked_at')->count();
            if ($admins <= 1) return back()->withErrors(['role' => 'В системе должен остаться хотя бы один администратор.']);
        }
        if ($currentRole !== $data['role']) {
            DB::transaction(function () use ($request, $user, $currentRole, $data): void {
                $user->roles()->sync([Role::where('slug', $data['role'])->value('id')]);
                AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'user.role_changed', 'subject_type' => User::class, 'subject_id' => $user->id, 'summary' => "Роль пользователя {$user->email}: {$currentRole} → {$data['role']}", 'created_at' => now()]);
            });
        }

        return back()->with('status', 'Роль пользователя обновлена.');
    }

    public function block(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['block_reason' => 'Нельзя заблокировать собственный аккаунт.']);
        }

        $data = $request->validate(['block_reason' => ['required', 'string', 'min:3', 'max:500']]);
        if ($user->hasRole('admin') && Role::where('slug', 'admin')->firstOrFail()->users()->whereNull('blocked_at')->count() <= 1) {
            return back()->withErrors(['block_reason' => 'Нельзя заблокировать последнего администратора.']);
        }
        if ($user->isBlocked()) {
            return back()->with('status', 'Этот аккаунт уже заблокирован.');
        }

        DB::transaction(function () use ($request, $user, $data): void {
            $user->forceFill(['blocked_at' => now(), 'blocked_by' => $request->user()->id, 'block_reason' => trim($data['block_reason']), 'remember_token' => Str::random(60)])->save();
            AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'user.blocked', 'subject_type' => User::class, 'subject_id' => $user->id, 'summary' => "Аккаунт {$user->email} заблокирован: ".trim($data['block_reason']), 'created_at' => now()]);
        });

        return back()->with('status', 'Аккаунт заблокирован.');
    }

    public function unblock(Request $request, User $user): RedirectResponse
    {
        if (! $user->isBlocked()) {
            return back()->with('status', 'Этот аккаунт уже активен.');
        }

        DB::transaction(function () use ($request, $user): void {
            $user->forceFill(['blocked_at' => null, 'blocked_by' => null, 'block_reason' => null])->save();
            AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'user.unblocked', 'subject_type' => User::class, 'subject_id' => $user->id, 'summary' => "Доступ к аккаунту {$user->email} восстановлен.", 'created_at' => now()]);
        });

        return back()->with('status', 'Доступ к аккаунту восстановлен.');
    }
}
