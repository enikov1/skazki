@extends('layouts.app')
@section('title', 'СказкоМир — детские сказки и аудио онлайн — СказкоМир')
@section('description', 'СказкоМир — детская библиотека историй, аудио и занятий.')
@if(request()->query() || request()->query('page'))@section('robots', 'noindex,follow')@endif
@section('content')
@php
  $homeStories = $stories->getCollection();
  $heroStory = $homeStories->first();
  $audioStories = $homeStories->filter(fn ($story) => filled($story->audio_path))->take(6);
  $coverClasses = ['c1','c2','c3','c4','c5','c6','c7','c8','c9','c10','c11','c12'];
@endphp
<form class="searchbar" id="searchForm" role="search" action="{{ route('stories.index') }}" method="get">
  <label class="visually-hidden" for="globalSearch">Поиск по библиотеке</label>
  <span aria-hidden="true"><svg class="icon"><use href="{{ asset('assets/icons.svg') }}#search"></use></svg></span>
  <input id="globalSearch" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Найти сказку, рассказ, стихотворение или загадку…">
  <button type="submit" aria-label="Найти"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#search"></use></svg></button>
</form>
<div class="layout">
  <div class="maincol">
    <section class="hero">
      <div>
        <span class="eyebrow"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#star"></use></svg> Сказка, которую выбирают сегодня</span>
        <h1>Читайте и слушайте вместе</h1>
        <p>Любимые истории для маленьких читателей и их родителей.</p>
        @if($heroStory)<a class="btn" href="{{ route('stories.show', $heroStory) }}">Читать сказку →</a>@endif
        <a class="btn secondary" href="{{ route('audio.index') }}">Слушать</a>
      </div>
      <a class="hero-book" href="{{ $heroStory ? route('stories.show', $heroStory) : route('home') }}"><img src="{{ asset('assets/illustrations/family-reading.webp') }}" alt="Взрослый и ребёнок читают историю вместе"><small>Читаем вместе</small></a>
    </section>
    <div class="feature-row">
      <a class="feature-tile" href="{{ route('games.index') }}"><h3><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#puzzle"></use></svg> Загадки и игры</h3><p>Логические задания и викторины по любимым историям.</p><span class="tile-link">Выбрать занятие <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></span></a>
      <a class="feature-tile" href="{{ route('articles.index') }}"><h3><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#message"></use></svg> Идея для чтения</h3><p>Читайте по очереди: взрослый — страницу, ребёнок — абзац.</p><span class="tile-link">Советы родителям <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></span></a>
    </div>
    <div class="promo"><strong>Читайте в своём ритме</strong><p>Выбирайте историю по возрасту, настроению и времени.</p><a class="btn small" href="{{ route('stories.index') }}">Открыть библиотеку</a></div>
    <section class="section">
      <div class="section-head"><h2>Сказки</h2><a href="{{ route('stories.index') }}">Все сказки <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></div>
      <div class="book-row">
        @foreach($homeStories->take(6) as $index => $story)
          <a class="book-card" href="{{ route('stories.show', $story) }}" data-book-id="{{ $story->slug }}" data-genre="{{ $story->genre }}" data-age="{{ $story->age_min }}–{{ $story->age_max }} лет" data-search="{{ Str::lower($story->title.' '.$story->author.' '.$story->genre) }}">
            <div class="cover {{ $coverClasses[$index % count($coverClasses)] }}">@if($story->cover_path)<img class="cover-img" src="{{ asset($story->cover_path) }}" alt="Иллюстрация к сказке «{{ $story->title }}»" loading="lazy">@endif<span class="mini-title">{{ $story->title }}</span></div><h3>{{ $story->title }}</h3><div class="author">{{ $story->author ?: 'СказкоМир' }}</div>
          </a>
        @endforeach
      </div>
    </section>
    <section class="section bluebg">
      <div class="section-head"><h2>Ещё истории</h2><a href="{{ route('stories.index') }}">Все сказки <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></div>
      <div class="book-row">
        @foreach($homeStories->slice(3, 6) as $index => $story)
          <a class="book-card" href="{{ route('stories.show', $story) }}"><div class="cover {{ $coverClasses[($index + 3) % count($coverClasses)] }}">@if($story->cover_path)<img class="cover-img" src="{{ asset($story->cover_path) }}" alt="Иллюстрация к сказке «{{ $story->title }}»" loading="lazy">@endif<span class="mini-title">{{ $story->title }}</span></div><h3>{{ $story->title }}</h3><div class="author">{{ $story->author ?: 'СказкоМир' }}</div></a>
        @endforeach
      </div>
    </section>
    <section class="section bluebg">
      <div class="section-head"><h2>Аудиосказки</h2><a href="{{ route('audio.index') }}">Все материалы <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></div>
      <div class="book-row">
        @forelse($audioStories as $index => $story)
          <a class="book-card" href="{{ route('stories.show', $story) }}"><div class="cover {{ $coverClasses[$index % count($coverClasses)] }}">@if($story->cover_path)<img class="cover-img" src="{{ asset($story->cover_path) }}" alt="Иллюстрация к сказке «{{ $story->title }}»" loading="lazy">@endif<span class="mini-title">{{ $story->title }}</span><span class="audio-play"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#play"></use></svg></span></div><h3>{{ $story->title }}</h3><div class="author">{{ $story->author ?: 'СказкоМир' }}</div></a>
        @empty
          <p class="empty">Новые аудиосказки скоро появятся в библиотеке.</p>
        @endforelse
      </div>
    </section>
    <section class="section">
      <div class="section-head"><h2>Занятия для детей</h2><a href="{{ route('games.index') }}">Все занятия →</a></div>
      <div class="round-row">
        @foreach([['puzzle','Загадки',route('games.index')],['bulb','Викторины',route('games.index')],['palette','Раскраски',route('colorings.index')],['target','Аудиосказки',route('audio.index')],['book','Чтение',route('stories.index')]] as [$icon,$label,$url])
          <a class="round-card" href="{{ $url }}"><span class="round-icon"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#{{ $icon }}"></use></svg></span>{{ $label }}</a>
        @endforeach
      </div>
    </section>
  </div>
  <aside class="sidebar">
    <section class="sidebox"><h3><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#medal"></use></svg> Самые активные</h3><ul><li class="rank"><i>1</i>Миша<span>985</span></li><li class="rank"><i>2</i>Алиса<span>840</span></li><li class="rank"><i>3</i>Варвара<span>756</span></li><li class="rank"><i>4</i>Саша<span>691</span></li><li class="rank"><i>5</i>Марина<span>570</span></li></ul></section>
    <section class="sidebox"><h3><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#message"></use></svg> Последние заметки</h3><div class="recent-notes"><article class="recent-note"><div class="note-avatar note-peach">М</div><div class="note-content"><p>«Очень добрая сказка!»</p><a href="{{ $heroStory ? route('stories.show',$heroStory) : route('home') }}">Маша · {{ $heroStory?->title ?? 'Сказки' }}</a></div></article><article class="recent-note"><div class="note-avatar note-mint">И</div><div class="note-content"><p>«Читали всей семьёй»</p><a href="{{ route('articles.index') }}">Илья · Родителям</a></div></article><article class="recent-note"><div class="note-avatar note-lilac">С</div><div class="note-content"><p>«Понравился герой»</p><a href="{{ $homeStories->get(1) ? route('stories.show',$homeStories->get(1)) : route('home') }}">Соня · {{ $homeStories->get(1)?->title ?? 'Сказки' }}</a></div></article></div><a class="sidebox-more" href="{{ route('articles.index') }}">Все отзывы читателей →</a></section>
    <section class="sidebox"><h3><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#heart"></use></svg> Популярное</h3><ul>@foreach($homeStories->take(5) as $story)<li><a href="{{ route('stories.show',$story) }}">{{ $story->title }} <span class="rating"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#heart"></use></svg> 4.9</span></a></li>@endforeach</ul></section>
    <section class="sidebox"><h3>Подборки</h3><ul><li><a href="{{ route('stories.index', ['genre' => 'Народные сказки']) }}">Русские народные <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></li><li><a href="{{ route('stories.index', ['age' => 5]) }}">Для малышей <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></li><li><a href="{{ route('audio.index') }}">Слушать сказки <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></li><li><a href="{{ route('games.index') }}">Загадки и игры <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></li></ul></section>
  </aside>
</div>
{{ $stories->links('vendor.pagination.skazki') }}
@endsection
