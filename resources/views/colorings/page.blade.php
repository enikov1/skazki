@extends('layouts.app')
@section('title', $page->title.' — раскраска | СказкоМир')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('colorings.index') }}">Раскраски</a><span>/</span><a href="{{ route('colorings.category', $category) }}">{{ $category->name }}</a><span>/</span><span aria-current="page">{{ $page->title }}</span></nav>
<section class="page-panel coloring-work"><p class="eyebrow">{{ $category->name }}</p><h1 class="page-title">{{ $page->title }}</h1><div class="coloring-work-image"><img src="{{ asset($page->outline_path) }}" alt="Контур рисунка «{{ $page->title }}»"></div><p class="notice">Инструменты раскрашивания появятся в следующем этапе мастерской.</p><a class="button button-soft" href="{{ route('colorings.category', $category) }}">← Другие раскраски</a></section>
@endsection
