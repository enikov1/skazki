@extends('layouts.app')
@section('title', 'Сказки — управление контентом')
@section('content')
<div class="admin-topline"><div><p class="eyebrow">Библиотека</p><h1>Сказки</h1></div><a class="button" href="{{ route('admin.stories.create') }}">Добавить сказку</a></div>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form class="filter-panel admin-content-filters" method="get" action="{{ route('admin.stories.index') }}" data-ajax-filter>
    <label class="field search-field"><span>Поиск</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Название или адрес страницы"></label>
    <label class="field"><span>Статус</span><select name="status"><option value="">Любой статус</option>@foreach(\App\Enums\EditorialStatus::cases() as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>@endforeach</select></label>
    <label class="field"><span>Жанр</span><select name="genre"><option value="">Все жанры</option>@foreach($genres as $genre)<option value="{{ $genre }}" @selected(($filters['genre'] ?? '') === $genre)>{{ $genre }}</option>@endforeach</select></label>
    <button class="button" type="submit">Найти</button><a class="button button-quiet" href="{{ route('admin.stories.index') }}">Сбросить</a>
</form>
<div class="admin-ajax-results" id="adminStoryResults" data-ajax-results aria-live="polite">@include('admin.stories.results')</div>
@endsection
