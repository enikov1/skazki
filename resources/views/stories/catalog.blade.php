@extends('layouts.app')
@section('title', 'Все сказки — каталог | СказкоМир')
@section('description', 'Каталог сказок для детей: ищите истории по названию, жанру и возрасту, выбирайте сказку для чтения и прослушивания.')
@if(request()->query())@section('robots', 'noindex,follow')@endif
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span aria-hidden="true">/</span><span aria-current="page">Все сказки</span></nav>
<section class="page-panel story-catalog">
  <div class="story-catalog-heading"><p class="eyebrow">Детская библиотека</p><h1>Все сказки</h1><p>Выберите историю по названию, жанру или возрасту.</p></div>
  <form class="story-catalog-filters" action="{{ route('stories.index') }}" method="get" role="search" data-story-catalog-filter>
    <label class="field"><span>Поиск</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Название или описание"></label>
    <label class="field"><span>Жанр</span><select name="genre"><option value="">Все жанры</option>@foreach($genres as $genre)<option value="{{ $genre }}" @selected(($filters['genre'] ?? '') === $genre)>{{ $genre }}</option>@endforeach</select></label>
    <label class="field"><span>Возраст</span><select name="age"><option value="">Любой возраст</option>@foreach([3,4,5,6,7,8,9,10,11,12] as $age)<option value="{{ $age }}" @selected((string)($filters['age'] ?? '') === (string)$age)>{{ $age }} лет</option>@endforeach</select></label>
    <button class="button button-primary" type="submit">Найти сказку</button>
  </form>
  <div class="story-catalog-results" data-story-catalog-results aria-live="polite">@include('stories.catalog-results', ['stories' => $stories])</div>
  @if($landingPages->isNotEmpty())<nav class="catalog-seo-links" aria-label="Подборки сказок"><h2>Сказки по жанрам, авторам и возрасту</h2><div>@foreach($landingPages as $landingPage)<a href="{{ $landingPage->publicUrl() }}">{{ $landingPage->title }}</a>@endforeach</div></nav>@endif
</section>
@endsection
