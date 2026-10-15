<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    use RespondsToAjax;

    public function index(Request $request): View|JsonResponse
    {
        Gate::authorize('viewAny', User::class);
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

        $roles = Role::orderBy('id')->get(['id', 'name', 'slug']);
        if ($request->expectsJson()) {
            return response()->json(['html' => view('admin.users.results', compact('users', 'roles'))->render(), 'count' => $users->total()]);
        }

        return view('admin.users.index', compact('users', 'roles', 'filters'));
    }

    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        Gate::authorize('changeRole', $user);
        $data = $request->validate(['role' => ['required', Rule::in(['user', 'editor', 'moderator', 'admin'])]]);
        $currentRole = $user->roles()->value('slug');
        if ($request->user()->is($user) && $data['role'] !== 'admin') return $this->reject($request, 'role', 'Нельзя убрать роль администратора у своей учётной записи.');
        if ($currentRole === 'admin' && $data['role'] !== 'admin') {
            $admins = Role::where('slug', 'admin')->firstOrFail()->users()->whereNull('blocked_at')->count();
            if ($admins <= 1) return $this->reject($request, 'role', 'В системе должен остаться хотя бы один администратор.');
        }
        if ($currentRole !== $data['role']) {
            DB::transaction(function () use ($request, $user, $currentRole, $data): void {
                $user->roles()->sync([Role::where('slug', $data['role'])->value('id')]);
                AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'user.role_changed', 'subject_type' => User::class, 'subject_id' => $user->id, 'summary' => "Роль пользователя {$user->email}: {$currentRole} → {$data['role']}", 'created_at' => now()]);
            });
        }

        return $this->respond($request, 'Роль пользователя обновлена.');
    }

    public function block(Request $request, User $user): RedirectResponse|JsonResponse
    {
        Gate::authorize('block', $user);
        if ($request->user()->is($user)) {
            return $this->reject($request, 'block_reason', 'Нельзя заблокировать собственный аккаунт.');
        }

        $data = $request->validate(['block_reason' => ['required', 'string', 'min:3', 'max:500']]);
        if ($user->hasRole('admin') && Role::where('slug', 'admin')->firstOrFail()->users()->whereNull('blocked_at')->count() <= 1) {
            return $this->reject($request, 'block_reason', 'Нельзя заблокировать последнего администратора.');
        }
        if ($user->isBlocked()) {
            return $this->respond($request, 'Этот аккаунт уже заблокирован.');
        }

        DB::transaction(function () use ($request, $user, $data): void {
            $user->forceFill(['blocked_at' => now(), 'blocked_by' => $request->user()->id, 'block_reason' => trim($data['block_reason']), 'remember_token' => Str::random(60)])->save();
            AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'user.blocked', 'subject_type' => User::class, 'subject_id' => $user->id, 'summary' => "Аккаунт {$user->email} заблокирован: ".trim($data['block_reason']), 'created_at' => now()]);
        });

        return $this->respond($request, 'Аккаунт заблокирован.');
    }

    public function unblock(Request $request, User $user): RedirectResponse|JsonResponse
    {
        Gate::authorize('unblock', $user);
        if (! $user->isBlocked()) {
            return $this->respond($request, 'Этот аккаунт уже активен.');
        }

        DB::transaction(function () use ($request, $user): void {
            $user->forceFill(['blocked_at' => null, 'blocked_by' => null, 'block_reason' => null])->save();
            AuditLog::create(['actor_id' => $request->user()->id, 'action' => 'user.unblocked', 'subject_type' => User::class, 'subject_id' => $user->id, 'summary' => "Доступ к аккаунту {$user->email} восстановлен.", 'created_at' => now()]);
        });

        return $this->respond($request, 'Доступ к аккаунту восстановлен.');
    }

    private function reject(Request $request, string $field, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422);
        }

        return back()->withErrors([$field => $message]);
    }

}
