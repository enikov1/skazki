@extends('layouts.app')
@section('title', $page->title.' — раскраска | СказкоМир')
@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($item, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item[0], 'item' => $item[1]], [['Главная', route('home')], ['Раскраски', route('colorings.index')], [$category->name, route('colorings.category', $category)], [$page->title, route('colorings.page', [$category, $page])]], array_keys([['Главная', route('home')], ['Раскраски', route('colorings.index')], [$category->name, route('colorings.category', $category)], [$page->title, route('colorings.page', [$category, $page])]]))], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span aria-hidden="true">/</span><a href="{{ route('colorings.index') }}">Раскраски</a><span aria-hidden="true">/</span><a href="{{ route('colorings.category', $category) }}">{{ $category->name }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ $page->title }}</span></nav>

<div class="coloring-detail-page" data-coloring-studio data-coloring-id="{{ $page->id }}" @auth data-coloring-progress-url="{{ route('colorings.progress.store', [$category, $page]) }}" @endauth>
  <header class="coloring-detail-header">
    <div class="coloring-detail-title">
      <a class="coloring-category-badge" href="{{ route('colorings.category', $category) }}">{{ $category->name }}</a>
      <h1>{{ $page->title }}</h1>
      <p class="coloring-score" aria-live="polite"><span aria-hidden="true">★</span> <strong data-coloring-average>{{ $rating->total ? number_format((float) $rating->average, 1, ',', ' ') : '—' }}</strong> <span data-coloring-rating-count>{{ $rating->total }}</span> {{ trans_choice('оценка|оценки|оценок', $rating->total) }}</p>
    </div>
    <div class="coloring-detail-actions">
      @auth<form method="post" action="{{ route('colorings.favorite', [$category, $page]) }}" data-ajax-form data-coloring-favorite data-saved-label="В избранном" data-unsaved-label="В избранное">@csrf<button class="button button-soft" type="submit" aria-pressed="{{ $isFavorited ? 'true' : 'false' }}">{{ $isFavorited ? 'В избранном' : 'Добавить в избранное' }}</button></form>
      @else<a class="button button-soft" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">Добавить в избранное</a>@endauth
      <a class="button button-quiet" href="{{ route('colorings.category', $category) }}">← Другие раскраски</a>
    </div>
  </header>

  <section class="coloring-workspace" aria-label="Рабочее место для раскрашивания">
    <div class="coloring-canvas-column">
      @auth<script type="application/json" data-coloring-cloud-strokes>@json($savedStrokes)</script>@endauth
      <div class="coloring-studio-canvas"><canvas data-coloring-canvas role="img" aria-label="Поле для раскрашивания рисунка «{{ $page->title }}». Рисуйте мышью или пальцем."></canvas><img src="{{ asset($page->outline_path) }}" alt="" data-coloring-outline hidden></div>
      <div class="coloring-studio-actions" aria-label="Действия с рисунком"><button class="button button-quiet" type="button" data-coloring-undo>Отменить штрих</button><button class="button button-quiet" type="button" data-coloring-clear>Очистить</button><span class="coloring-actions-spacer" aria-hidden="true"></span><button class="button button-soft" type="button" data-coloring-download>Скачать PNG</button><button class="button button-soft" type="button" data-coloring-print><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#printer"></use></svg> Распечатать</button></div>
      <p class="coloring-save-status" data-coloring-save-status role="status" aria-live="polite">Рисунок сохраняется на этом устройстве автоматически.</p>
    </div>

    <aside class="coloring-tools-panel" aria-label="Инструменты рисования">
      <section class="coloring-tool-section"><h2>Инструмент</h2><div class="coloring-tool-group" role="group" aria-label="Выберите инструмент"><button class="button button-quiet is-active" type="button" data-coloring-tool="brush" aria-pressed="true">Кисть</button><button class="button button-quiet" type="button" data-coloring-tool="eraser" aria-pressed="false">Ластик</button></div></section>
      <section class="coloring-tool-section"><h2>Разме�