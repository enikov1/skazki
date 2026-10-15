@extends('layouts.app')
@section('title', 'Сказки для детей — читать и слушать | СказкоМир')
@section('description', 'Большая библиотека сказок для детей: выбирайте историю по возрасту, жанру и настроению.')
@if(request()->query() || request()->query('page'))@section('robots', 'noindex,follow')@endif
@section('content')
<section class="welcome-card">
  <div><p class="eyebrow">Библиотека семейных историй</p><h1>Найдём сказку <span>на сегодня</span></h1><p class="lead">Выбирайте историю по возрасту и настроению. Читайте вместе, открывайте новых героев и возвращайтесь к любимым книгам.</p></div>
  <div class="welcome-art" aria-hidden="true"><span>📚</span><span>☀️</span><span>🌷</span></div>
</section>
<section class="catalog-section" aria-labelledby="catalog-heading">
 <div class="section-heading"><div><p class="eyebrow">Наша библиотека</p><h2 id="catalog-heading">Сказки и истории</h2></div><span class="result-count">{{ $stories->total() }} историй</span></div>
 <form class="filter-panel" method="get" action="{{ route('home') }}">
  <label class="field search-field"><span>Поиск</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Название сказки или герой"></label>
  <label class="field"><span>Жанр</span><select name="genre"><option value="">Все жанры</option>@foreach($genres as $genre)<option value="{{ $genre }}" @selected(($filters['genre'] ?? '') === $genre)>{{ $genre }}</option>@endforeach</select></label>
  <label class="field age-field"><span>Возраст ребёнка</span><select name="age"><option value="">Любой возраст</option>@foreach(range(3, 12) as $age)<option value="{{ $age }}" @selected((string)($filters['age'] ?? '') === (string)$age)>{{ $age }} лет</option>@endforeach</select></label>
  <button class="button" type="submit">Показать сказки</button>
  @if(count($filters))<a class="clear-filter" href="{{ route('home') }}">Сбросить</a>@endif
 </form>
 @if($stories->count())<div class="story-grid">@foreach($stories as $story)
  <article class="story-card"><a class="cover-link" href="{{ route('stories.show', $story) }}">@if($story->cover_path)<img class="story-cover" src="{{ asset($story->cover_path) }}" alt="Обложка сказки «{{ $story->title }}»" width="640" height="480" loading="lazy">@else<div class="cover-placeholder" aria-hidden="true">📖</div>@endif</a>
   <div class="story-card-body"><div class="story-meta"><span>{{ $story->genre }}</span><span>{{ $story->age_min }}–{{ $story->age_max }} лет</span></div><h3><a href="{{ route('stories.show', $story) }}">{{ $story->title }}</a></h3><p class="byline">{{ $story->author ?: 'СказкоМир' }} · {{ $story->reading_minutes }} мин</p><p>{{ $story->summary }}</p><a class="text-link" href="{{ route('stories.show', $story) }}">Открыть сказку <span aria-hidden="true">→</span></a></div>
  </article>
 @endforeach</div>{{ $stories->links('pagination::simple-tailwind') }}
 @else<div class="empty-state"><span aria-hidden="true">🔎</span><h3>Таких сказок пока нет</h3><p>Попробуйте изменить запрос или выбрать другой возраст.</p><a class="button button-soft" href="{{ route('home') }}">Посмотреть все сказки</a></div>@endif
</section>
@endsection
