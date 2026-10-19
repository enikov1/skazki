@extends('layouts.app')
@section('title', 'Статьи для родителей — СказкоМир')
@section('description', 'Практичные идеи о чтении, выборе книг и семейных занятиях.')
@if(request()->query())@section('robots', 'noindex,follow')@endif
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span aria-hidden="true">/</span><span aria-current="page">Родителям</span></nav>
<section class="page-heading-art"><div><h1 class="page-title">Статьи для родителей</h1><p class="lead">Практичные идеи о чтении, выборе книг и семейных занятиях.</p></div><img src="{{ asset('assets/illustrations/family-reading.svg') }}" alt="Медвежонок и зайчонок читают книгу"></section>
<section class="page-panel catalog-filters"><div class="filters"><span class="filter-label">Рубрика</span>@foreach(['Все','Читаем вместе','Практика','По возрасту','Разговор о книге','Развитие'] as $category)<a data-article-category class="pill {{ (($filters['category'] ?? '') === $category || ($category === 'Все' && empty($filters['category']))) ? 'active' : '' }}" href="{{ route('articles.index', array_filter(['category' => $category === 'Все' ? null : $category, 'q' => $filters['q'] ?? null])) }}">{{ $category }}</a>@endforeach</div>
<form class="searchbar" data-article-filter method="get" action="{{ route('articles.index') }}"><label class="visually-hidden" for="articleSearch">Поиск по статьям</label><span aria-hidden="true"><svg class="icon"><use href="{{ asset('assets/icons.svg') }}#search"></use></svg></span><input id="articleSearch" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Например, чтение перед сном"><input type="hidden" name="category" value="{{ $filters['category'] ?? '' }}"><button type="submit" aria-label="Найти"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#search"></use></svg></button></form>
</section>
<section class="page-panel" data-article-results aria-live="polite">@include('articles.results')</section>
@endsection
