@extends('layouts.app')
@section('title', $story->seo_title ?: $story->title.' — читать сказку | СказкоМир')
@section('description', $story->seo_description ?: $story->summary)
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span aria-hidden="true">/</span><a href="{{ route('stories.index') }}">Все сказки</a><span aria-hidden="true">/</span><span aria-current="page">{{ $story->title }}</span></nav>
@include('stories.partials.print-sheet')
<section class="page-panel story-page-panel">
  <div class="detail">
    <div class="cover c1">@if($story->cover_path)<img class="cover-img" src="{{ asset($story->cover_path) }}" alt="Иллюстрация к сказке «{{ $story->title }}">@endif<span class="mini-title">{{ $story->title }}</span></div>
    <div>
      <p class="eyebrow">{{ $story->genre }}</p><h1 class="story-title">{{ $story->title }}</h1><div class="detail-meta">{{ $story->author ?: 'СказкоМир' }} · {{ $story->age_min }}–{{ $story->age_max }} лет · {{ $story->reading_minutes }} минут</div>
      <div class="meta-chips">
        <span>Жанр: {{ $story->genre }}</span><span>Возраст: {{ $story->age_min }}–{{ $story->age_max }} лет</span><span>Чтение: {{ $story->reading_minutes }} минут</span>
        @if($story->series_name)
          <span>Серия: {{ $story->series_name }}</span>
        @endif
        <span>Отзывы: <b>{{ $comments->total() }}</b></span>
      </div>
      @if($story->source_name || $story->source_url || $story->rights_holder)
        <p class="story-source-credit">
          @if($story->source_url)
            Источник: <a href="{{ $story->source_url }}" target="_blank" rel="noopener noreferrer">{{ $story->source_name ?: $story->source_url }}</a>
          @elseif($story->source_name)
            Источник: {{ $story->source_name }}
          @endif
          @if($story->rights_holder)
            <span> · Права: {{ $story->rights_holder }}</span>
          @endif
        </p>
      @endif
      @if($story->tags)
        <div class="meta-chips story-tags" aria-label="Теги сказки">
          @foreach($story->tags as $tag)
            <span>{{ $tag }}</span>
          @endforeach
        </div>
      @endif
      <div class="tool-row story-actions" data-print-hide><a class="btn story-action story-action-read" href="{{ route('stories.read', $story) }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#book"></use></svg> Читать историю</a>@if($story->audio_path)<a class="btn blue story-action story-action-listen" href="#storyAudio"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#play"></use></svg> Слушать</a>@endif<a class="pill story-action story-action-shelf" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bookmark"></use></svg> В мою полку</a><button class="pill story-action story-action-share" type="button" data-copy-link><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#share"></use></svg> Поделиться</button><button class="button button-quiet story-action story-action-print" type="button" data-print-page><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#printer"></use></svg> Распечатать</button></div>
      @if($story->audio_path)<div class="laravel-audio-box"><div class="player-title">Аудиосказка</div><div class="custom-audio-player" data-custom-audio><audio id="storyAudio" preload="metadata" src="{{ route('stories.audio', $story) }}" @auth data-audio-progress="{{ $audioProgress }}" data-audio-progress-url="{{ route('stories.audio.progress', $story) }}" @endauth>Ваш браузер не поддерживает воспроизведение аудио.</audio><button class="custom-audio-toggle" type="button" data-audio-toggle aria-label="Воспроизвести аудиосказку"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#play"></use></svg></button><span class="custom-audio-time" data-audio-current>0:00</span><input class="custom-audio-seek" type="range" min="0" max="1000" value="0" step="1" aria-label="Позиция воспроизведения" data-audio-seek><span class="custom-audio-time" data-audio-duration>0:00</span><label class="custom-audio-volume"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#volume"></use></svg><input type="range" min="0" max="1" value="1" step="0.05" aria-label="Громкость" data-audio-volume></label></div></div>@endif
    </div>
  </div>
</section>
<section class="article-rating" id="story-rating" aria-label="Оценка сказки" data-print-hide><div class="rating-score"><spa