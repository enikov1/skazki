@extends('layouts.app')
@section('title', 'Раскраски для детей — СказкоМир')
@section('description', 'Раскраски по темам для детей: выбирайте категорию и открывайте новые рисунки.')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><span aria-current="page">Раскраски</span></nav>
<header class="page-panel coloring-heading"><p class="eyebrow">Творческая мастерская</p><h1 class="page-title">Раскраски</h1><p class="lead">Выберите тему, найдите любимую картинку и раскрасьте её.</p></header>
<form class="coloring-search" method="get" action="{{ route('colorings.index') }}" data-coloring-filter><label class="field"><span class="sr-only">Найти категорию</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Найти тему раскраски"></label><button class="button" type="submit">Найти</button><a class="button button-quiet" href="{{ route('colorings.index') }}" data-coloring-reset>Сбросить</a></form>
<p class="coloring-gallery-link"><a class="button button-soft" href="{{ route('colorings.gallery') }}">Посмотреть галерею работ</a></p>
<div data-coloring-results aria-live="polite">@include('colorings.category-cards', ['categories' => $categories])</div>
@endsection
