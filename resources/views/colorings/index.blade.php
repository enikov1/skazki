@extends('layouts.app')
@section('title', 'Раскраски для детей — СказкоМир')
@section('description', 'Раскраски по темам для детей: выбирайте категорию и открывайте новые рисунки.')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><span aria-current="page">Раскраски</span></nav>
<header class="page-panel coloring-heading"><p class="eyebrow">Творческая мастерская</p><h1 class="page-title">Раскраски</h1><p class="lead">Выберите тему, найдите любимую картинку и раскрасьте её.</p></header>
@if($categories->isNotEmpty())<section class="coloring-category-grid" aria-label="Категории раскрасок">@foreach($categories as $category)<a class="coloring-category-card" href="{{ route('colorings.category', $category) }}"><span class="coloring-category-image">@if($category->cover_path)<img src="{{ asset($category->cover_path) }}" alt="" loading="lazy">@else<span aria-hidden="true">🎨</span>@endif</span><span class="coloring-category-copy"><strong>{{ $category->name }}</strong><small>{{ $category->pages_count }} {{ trans_choice('раскраска|раскраски|раскрасок', $category->pages_count) }}</small>@if($category->description)<span>{{ $category->description }}</span>@endif</span><span aria-hidden="true">→</span></a>@endforeach</section>@else<section class="page-panel empty-state"><span aria-hidden="true">🎨</span><h2>Новые раскраски скоро появятся</h2><p>Мы готовим категории и рисунки для мастерской.</p><a class="button button-soft" href="{{ route('home') }}">Вернуться к сказкам</a></section>@endif
@endsection
