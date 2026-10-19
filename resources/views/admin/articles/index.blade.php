@extends('layouts.app')
@section('title', 'Статьи — управление контентом')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><span>Статьи</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Журнал для родителей</p><h1>Статьи</h1></div><a class="button" href="{{ route('admin.articles.create') }}">Добавить статью</a></div>
<form class="filter-panel admin-content-filters" method="get" action="{{ route('admin.articles.index') }}" data-ajax-filter>
    <label class="field search-field"><span>Поиск</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Заголовок или адрес страницы"></label>
    <label class="field"><span>Статус</span><select name="status"><option value="">Любой статус</option>@foreach(\App\Enums\EditorialStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
    <label class="field"><span>Тема</span><select name="category"><option value="">Все темы</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>@endforeach</select></label>
    <button class="button" type="submit">Найти</button><a class="button button-quiet" href="{{ route('admin.articles.index') }}">Сбросить</a>
</form>
<div class="admin-ajax-results" id="adminArticleResults" data-ajax-results aria-live="polite">@include('admin.articles.results')</div>
@endsection
