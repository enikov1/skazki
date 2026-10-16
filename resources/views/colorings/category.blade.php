@extends('layouts.app')
@section('title', $category->name.' — раскраски | СказкоМир')
@section('description', $category->description ?: 'Раскраски в категории «'.$category->name.'».')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('colorings.index') }}">Раскраски</a><span>/</span><span aria-current="page">{{ $category->name }}</span></nav>
<header class="page-panel coloring-heading"><p class="eyebrow">Категория раскрасок</p><h1 class="page-title">{{ $category->name }}</h1>@if($category->description)<p class="lead">{{ $category->description }}</p>@endif</header>
@if($pages->isNotEmpty())<section class="coloring-page-grid">@foreach($pages as $page)<a class="coloring-page-card" href="{{ route('colorings.page', [$category, $page]) }}"><span>@if($page->preview_path)<img src="{{ asset($page->preview_path) }}" alt="" loading="lazy">@else<span aria-hidden="true">🖍️</span>@endif</span><strong>{{ $page->title }}</strong></a>@endforeach</section>{{ $pages->links('vendor.pagination.skazki') }}@else<section class="page-panel empty-state"><span aria-hidden="true">🖍️</span><h2>В этой категории пока нет рисунков</h2><p>Загляните позже или выберите другую тему.</p><a class="button button-soft" href="{{ route('colorings.index') }}">Все категории</a></section>@endif
@endsection
