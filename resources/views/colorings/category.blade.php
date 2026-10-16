@extends('layouts.app')
@section('title', $category->name.' — раскраски | СказкоМир')
@section('description', $category->description ?: 'Раскраски в категории «'.$category->name.'».')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('colorings.index') }}">Раскраски</a><span>/</span><span aria-current="page">{{ $category->name }}</span></nav>
<header class="page-panel coloring-heading"><p class="eyebrow">Категория раскрасок</p><h1 class="page-title">{{ $category->name }}</h1>@if($category->description)<p class="lead">{{ $category->description }}</p>@endif</header>
<form class="coloring-search" method="get" action="{{ route('colorings.category', $category) }}" data-coloring-filter><label class="field"><span class="sr-only">Найти раскраску</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Найти рисунок"></label><button class="button" type="submit">Найти</button><a class="button button-quiet" href="{{ route('colorings.category', $category) }}" data-coloring-reset>Сбросить</a></form>
<div data-coloring-results aria-live="polite">@include('colorings.page-cards', ['category' => $category, 'pages' => $pages])</div>
@endsection
