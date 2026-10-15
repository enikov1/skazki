@extends('layouts.app')
@section('title', 'Пользователи и роли — СказкоМир')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><span>Пользователи</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Доступ к сайту</p><h1>Пользователи и роли</h1><p class="lead">Ищите аккаунты, назначайте роли и управляйте доступом.</p></div><a class="button button-secondary" href="{{ route('admin.audit-logs.index') }}">Журнал действий</a></div>
@if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="admin-user-filters" method="get" action="{{ route('admin.users.index') }}">
    <label class="field"><span>Имя или почта</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Например, Маша или name@mail.ru"></label>
    <label class="field"><span>Роль</span><select name="role"><option value="">Все роли</option>@foreach($roles as $role)<option value="{{ $role->slug }}" @selected(($filters['role'] ?? '') === $role->slug)>{{ $role->name }}</option>@endforeach</select></label>
    <label class="field"><span>Доступ</span><select name="status"><option value="">Любой</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Активен</option><option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Заблокирован</option></select></label>
    <div class="admin-filter-actions"><button class="button" type="submit">Найти</button><a class="button button-quiet" href="{{ route('admin.users.index') }}">Сбросить</a></div>
</form>
<p class="result-count">Пользователей: {{ $users->total() }}</p>
<div class="table-wrap admin-users-table"><table><thead><tr><th>Пользователь</th><th>Роль</th><th>Доступ</th><th>Изменить роль</th><th>Управление</th></tr></thead><tbody>
@forelse($users as $user)
<tr><td><strong>{{ $user->name }}</strong><br><span>{{ $user->email }}</span></td><td>{{ $user->roles->first()?->name ?? 'Пользователь' }}</td><td>@if($user->isBlocked())<span class="status-pill status-pill-muted">Заблокирован</span><small> {{ $user->blocked_at->format('d.m.Y H:i') }} @if($user->blocker) · {{ $user->blocker->name }} @endif</small><small>{{ $user->block_reason }}</small>@else<span class="status-pill">Активен</span>@endif</td><td><form class="role-form" method="post" action="{{ route('admin.users.role', $user) }}">@csrf @method('patch')<select name="role" aria-label="Роль пользователя {{ $user->name }}">@foreach($roles as $role)<option value="{{ $role->slug }}" @selected($user->roles->contains('slug', $role->slug))>{{ $role->name }}</option>@endforeach</select><button class="button button-small" type="submit">Сохранить</button></form></td><td>@if(auth()->id() !== $user->id)@if($user->isBlocked())<form method="post" action="{{ route('admin.users.unblock', $user) }}">@csrf @method('patch')<button class="button button-small" type="submit">Вернуть доступ</button></form>@else<form class="block-user-form" method="post" action="{{ route('admin.users.block', $user) }}">@csrf @method('patch')<label class="field"><span class="sr-only">Причина блокировки {{ $user->name }}</span><input name="block_reason" maxlength="500" required minlength="3" placeholder="Причина блокировки"></label><button class="button button-small button-danger" type="submit">Заблокировать</button></form>@endif @else<span>Это вы</span>@endif</td></tr>
@empty<tr><td colspan="5"><div class="empty-state"><h2>Ничего не найдено</h2><p>Измените фильтры и попробуйте ещё раз.</p></div></td></tr>@endforelse
</tbody></table></div>
{{ $users->links('vendor.pagination.skazki') }}
@endsection
