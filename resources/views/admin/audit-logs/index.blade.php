@extends('layouts.app')
@section('title', 'Журнал действий — СказкоМир')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><a href="{{ route('admin.users.index') }}">Пользователи</a><span>/</span><span>Журнал действий</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Безопасность и доступ</p><h1>Журнал действий</h1><p class="lead">Здесь записаны изменения ролей и доступа к аккаунтам.</p></div><a class="button button-secondary" href="{{ route('admin.users.index') }}">К пользователям</a></div>
<form class="admin-user-filters" method="get" action="{{ route('admin.audit-logs.index') }}" data-ajax-filter><label class="field"><span>Поиск по описанию</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Имя или адрес почты"></label><label class="field"><span>Действие</span><select name="action"><option value="">Все действия</option><option value="user.blocked" @selected(($filters['action'] ?? '') === 'user.blocked')>Блокировка</option><option value="user.unblocked" @selected(($filters['action'] ?? '') === 'user.unblocked')>Восстановление доступа</option><option value="user.role_changed" @selected(($filters['action'] ?? '') === 'user.role_changed')>Смена роли</option></select></label><div class="admin-filter-actions"><button class="button" type="submit">Найти</button><a class="button button-quiet" href="{{ route('admin.audit-logs.index') }}">Сбросить</a></div></form>
<div data-ajax-results>
@include('admin.audit-logs.results', ['logs' => $logs])
</div>
@endsection
