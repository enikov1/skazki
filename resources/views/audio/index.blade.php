@extends('layouts.app')
@section('site_page', 'audio')
@section('title', 'Аудиосказки для детей — СказкоМир')
@section('description', 'Слушайте аудиосказки вместе: выбирайте историю по жанру и возрасту, добавляйте записи в очередь.')
@if(request()->query())@section('robots', 'noindex,follow')@endif
@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($item, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item[0], 'item' => $item[1]], [['Главная', route('home')], ['Аудиосказки', route('audio.index')]], array_keys([['Главная', route('home')], ['Аудиосказки', route('audio.index')]]))], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush
@section('content')
<nav class="breadcrumbs"><a href="{{ route('home') }}">Главная</a><span>/</span><span>Аудиосказки</span></nav>
<div class="page-heading-art"><div><h1 class="page-title">Аудиосказки</h1><p class="lead">Устройтесь поудобнее, наденьте наушники и отправляйтесь в сказочное путешествие.</p></div><img src="{{ asset('assets/illustrations/audio-story.webp') }}" alt="Ребёнок слушает сказку в наушниках" fetchpriority="high"></div>
<section class="page-panel audio-catalog-panel">
  <div class="section-head audio-catalog-title"><div><h2>Выберите историю</h2><p>Все опубликованные аудиосказки — рядом, а очередь поможет слушать их одну за другой.</p></div><span class="audio-ready-count" data-audio-total>{{ $stories->total() }} аудиозаписей</span></div>
  <form class="audio-filter-panel" method="get" action="{{ route('audio.index') }}" data-audio-filter aria-label="Фильтры аудиосказок">
    <label class="field"><span>Поиск</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Название или автор"></label>
    <label class="field"><span>Жанр</span><select name="genre"><option value="">Все жанры</option>@foreach($genres as $genre)<option value="{{ $genre }}" @selected(($filters['genre'] ?? '') === $genre)>{{ $genre }}</option>@endforeach</select></label>
    <label class="field"><span>Возраст</span><select name="age"><option value="">Любой возраст</option><option value="3-5" @selected(($filters['age'] ?? '') === '3-5')>3–5 лет</option><option value="5-8" @selected(($filters['age'] ?? '') === '5-8')>5–8 лет</option><option value="9-12" @selected(($filters['age'] ?? '') === '9-12')>9–12 лет</option></select></label>
    <label class="field"><span>Сортировка</span><select name="sort"><option value="available" @selected(($filters['sort'] ?? 'available') === 'available')>Сначала новые</option><option value="title" @selected(($filters['sort'] ?? '') === 'title')>По названию</option></select></label>
    <div class="audio-filter-actions"><button class="pill" type="submit">Применить</button><a class="pill" href="{{ route('audio.index') }}" data-audio-reset>Сбросить</a></div>
  </form>
  <p class="catalog-count" role="status" aria-live="polite" data-audio-count>Найдено аудиозаписей: {{ $stories->total() }}</p>
  <div data-audio-results>@include('audio.results')</div>
  @if($landingPages->isNotEmpty())<nav class="catalog-seo-links" aria-label="Подборки аудиосказок"><h2>Аудиосказки по жанрам, авторам и возрасту</h2><div>@foreach($landingPages as $landingPage)<a href="{{ $landingPage->publicUrl() }}">{{ $landingPage->title }}</a>@endforeach</div></nav>@endif
</section>
@include('audio.player')
@endsection
