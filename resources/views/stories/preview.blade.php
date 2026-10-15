@extends('layouts.app')
@section('title', 'Предпросмотр: '.$story->title.' — СказкоМир')
@section('description', $story->seo_description ?: $story->summary)
@section('robots', 'noindex,nofollow')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><a href="{{ route('admin.stories.edit', $story) }}">Редактирование сказки</a><span>/</span><span>Предпросмотр</span></nav>
<div class="preview-banner" role="status"><strong>Предпросмотр · {{ $statusLabel }}</strong><span>Страница доступна редакторам материала и не попадёт в поиск.</span></div>
<section class="page-panel story-page-panel"><div class="detail"><div class="cover c1">@if($story->cover_path)<img class="cover-img" src="{{ asset($story->cover_path) }}" alt="Иллюстрация к сказке «{{ $story->title }}">@endif<span class="mini-title">{{ $story->title }}</span></div><div><p class="eyebrow">{{ $story->genre }}</p><h1 class="story-title">{{ $story->title }}</h1><div class="detail-meta">{{ $story->author ?: 'СказкоМир' }} · {{ $story->age_min }}–{{ $story->age_max }} лет · {{ $story->reading_minutes }} минут</div><div class="meta-chips"><span>Возраст: {{ $story->age_min }}–{{ $story->age_max }} лет</span><span>Чтение: {{ $story->reading_minutes }} минут</span></div>@if($story->audio_path)<div class="laravel-audio-box"><div class="player-title">Аудиосказка</div><audio controls preload="metadata" src="{{ asset($story->audio_path) }}">Ваш браузер не поддерживает воспроизведение аудио.</audio></div>@endif</div></div></section>
<section class="story-description story-detail"><h2>О сказке</h2><p>{{ $story->summary }}</p><p>Автор: {{ $story->author ?: 'СказкоМир' }}. Категория: {{ $story->genre }}.</p><article class="reading-text reader-theme-{{ $readerTheme }}" style="font-size: {{ $readerFontSize }}px; --reader-max-width: {{ $readerWidth }}px">{!! nl2br(e($story->body)) !!}</article></section>
<a class="button button-soft" href="{{ route('admin.stories.edit', $story) }}">Вернуться к редактированию</a>
@endsection
