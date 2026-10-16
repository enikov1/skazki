@extends('layouts.app')
@section('title', 'Игры и викторины для детей — СказкоМир')
@section('description', 'Играйте и проверяйте, что запомнилось из любимых сказок.')
@if(request()->query())@section('robots', 'noindex,follow')@endif
@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($item, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item[0], 'item' => $item[1]], [['Главная', route('home')], ['Игры и занятия', route('games.index')]], array_keys([['Главная', route('home')], ['Игры и занятия', route('games.index')]]))], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush
@section('content')
<nav class="breadcrumbs"><a href="{{ route('home') }}">Главная</a><span>/</span><span aria-current="page">Игры и занятия</span></nav>
<header class="page-panel games-heading"><p class="eyebrow">Играем и вспоминаем</p><h1 class="page-title">Игры и викторины</h1><p class="lead">Пройдите викторину по сказке, найдите отличия или соберите мозаику.</p></header>
<nav class="game-type-nav" aria-label="Виды игр"><a class="active" href="{{ route('games.index') }}">Викторины</a><a href="{{ route('games.differences.index') }}">Найди отличия</a><a href="{{ route('games.mosaics.index') }}">Мозаики</a></nav>
<section class="games-catalog" aria-labelledby="games-title">
    <div class="section-heading"><div><p class="eyebrow">Задания по сказкам</p><h2 id="games-title">Выберите викторину</h2></div><span class="result-count" data-games-count>{{ $stories->total() }} {{ trans_choice('викторина|викторины|викторин', $stories->total()) }}</span></div>
    <form class="games-filter" method="get" action="{{ route('games.index') }}" data-games-filter>
        <label class="field"><span>Поиск</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Название сказки"></label>
        <label class="field"><span>Жанр</span><select name="genre"><option value="">Все жанры</option>@foreach($genres as $genre)<option value="{{ $genre }}" @selected(($filters['genre'] ?? '') === $genre)>{{ $genre }}</option>@endforeach</select></label>
        <label class="field"><span>Возраст</span><select name="age"><option value="">Любой возраст</option><option value="3-5" @selected(($filters['age'] ?? '') === '3-5')>3–5 лет</option><option value="5-8" @selected(($filters['age'] ?? '') === '5-8')>5–8 лет</option><option value="9-12" @selected(($filters['age'] ?? '') === '9-12')>9–12 лет</option></select></label>
        <div class="games-filter-actions"><button class="button" type="submit">Найти</button><a class="button button-quiet" href="{{ route('games.index') }}" data-games-reset>Сбросить</a></div>
    </form>
    <p class="catalog-count" role="status" aria-live="polite">Найдено викторин: <span data-games-total>{{ $stories->total() }}</span></p>
    <div data-games-results>@include('games.results')</div>
</section>
@endsection
