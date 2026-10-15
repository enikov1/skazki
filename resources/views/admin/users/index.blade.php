@extends('layouts.app')
@section('title', 'Пользователи и роли — СказкоМир')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><span>Пользователи</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Доступ к сайту</p><h1>Пользователи и роли</h1><p class="lead">Ищите аккаунты, назначайте роли и управляйте доступом.</p></div><a class="button button-secondary" href="{{ route('admin.audit-logs.index') }}">Журнал действий</a></div>
@if(session('status'))<div class="form-success" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="admin-user-filters" method="get" action="{{ route('admin.users.index') }}" data-ajax-filter>
    <label class="field"><span>Имя или почта</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Например, Маша или name@mail.ru"></label>
    <label class="field"><span>Роль</span><select name="role"><option value="">Все роли</option>@foreach($roles as $role)<option value="{{ $role->slug }}" @selected(($filters['role'] ?? '') === $role->slug)>{{ $role->name }}</option>@endforeach</select></label>
    <label class="field"><span>Доступ</span><select name="status"><option value="">Любой</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Активен</option><option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Заблокирован</option></select></label>
    <div class="admin-filter-actions"><button class="button" type="submit">Найти</button><a class="button button-quiet" href="{{ route('admin.users.index') }}">Сбросить</a></div>
</form>
<div data-ajax-results>
@include('admin.users.results', ['users' => $users, 'roles' => $roles])
</div>
@endsection
