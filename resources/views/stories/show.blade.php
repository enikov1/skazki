@extends('layouts.app')
@section('title', $story->seo_title ?: $story->title.' — читать сказку | СказкоМир')
@section('description', $story->seo_description ?: $story->summary)
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span aria-hidden="true">/</span><a href="{{ route('home') }}">Сказки</a><span aria-hidden="true">/</span><span aria-current="page">{{ $story->title }}</span></nav>
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
      <div class="tool-row"><a class="btn" href="{{ url('/demo/read.html?id='.urlencode($story->slug)) }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#book"></use></svg> Читать историю</a>@if($story->audio_path)<a class="btn blue" href="#storyAudio"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#play"></use></svg> Слушать</a>@endif<a class="pill" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bookmark"></use></svg> В мою полку</a><button class="pill" type="button" data-copy-link><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#share"></use></svg> Поделиться</button></div>
      @if($story->audio_path)<div class="laravel-audio-box"><div class="player-title">Аудиосказка</div><audio id="storyAudio" controls preload="metadata" src="{{ route('stories.audio', $story) }}" @auth data-audio-progress="{{ $audioProgress }}" data-audio-progress-url="{{ route('stories.audio.progress', $story) }}" @endauth>Ваш браузер не поддерживает воспроизведение аудио.</audio></div>@endif
    </div>
  </div>
</section>
<section class="story-description story-detail"><h2>О сказке</h2><p>{{ $story->summary }}</p><p>Автор: {{ $story->author ?: 'СказкоМир' }}. Категория: {{ $story->genre }}. Подходит для детей {{ $story->age_min }}–{{ $story->age_max }} лет.</p><article class="reading-text reader-theme-{{ $readerTheme }}" style="font-size: {{ $readerFontSize }}px; --reader-max-width: {{ $readerWidth }}px">{!! app(\App\Support\BBEditor::class)->render($story->body) !!}</article></section>
<section class="page-panel story-comments" id="comments"><h2>Отзывы о сказке</h2><p class="notice">Поделитесь впечатлением о сказке — ваш отзыв появится после проверки.</p>
@auth<form class="form-grid" method="post" action="{{ route('comments.store', $story) }}" data-ajax-form data-ajax-clear>@csrf<label class="field full"><span>Комментарий</span><textarea name="body" rows="4" minlength="3" maxlength="2000" required placeholder="Что вам запомнилось?"></textarea></label>@error('body')<p class="field-error">{{ $message }}</p>@enderror<div class="field full"><button class="btn" type="submit">Оставить отзыв</button></div></form>
@else<p class="notice">Чтобы оставить отзыв, <a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">войдите в аккаунт</a>.</p>@endauth
<div class="comment-list">@forelse($comments as $comment)<article class="comment" id="comment-{{ $comment->id }}"><div class="comment-heading"><b>{{ $comment->user->name }}</b><time datetime="{{ $comment->created_at->toAtomString() }}">{{ $comment->created_at->translatedFormat('j F Y') }}</time></div><p>{{ $comment->body }}</p><div class="laravel-comment-actions">@foreach(['like'=>'Нравится','dislike'=>'Не нравится'] as $vote=>$label)<form method="post" action="{{ route('comments.vote', $comment) }}" data-ajax-form>@csrf<input type="hidden" name="vote" value="{{ $vote }}"><button class="pill" type="submit">{{ $label }} · {{ $vote === 'like' ? $comment->likes_count : $comment->dislikes_count }}</button></form>@endforeach</div></article>@empty<div class="empty">💬<p>Пока нет отзывов. Поделитесь первым впечатлением.</p></div>@endforelse</div>{{ $comments->links('vendor.pagination.skazki') }}</section>
<section class="quiz-callout"><span class="quiz-mark"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bulb"></use></svg></span><div><span class="eyebrow">Проверьте, что запомнилось</span><h2>Викторина по сказке «{{ $story->title }}»</h2><p>Короткие вопросы о героях и событиях истории — пройдите после чтения.</p></div><a class="btn" href="{{ url('/demo/quiz.html?id='.urlencode($story->slug)) }}">Пройти викторину <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></section>
@if($related->isNotEmpty())<section class="page-panel related-section"><div class="section-head"><div><span class="eyebrow">Продолжите чтение</span><h2>Другие сказки</h2></div><a href="{{ route('home', ['genre' => $story->genre]) }}">Вся библиотека <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></div><div class="book-row">@foreach($related as $index => $item)<a class="book-card" href="{{ route('stories.show', $item) }}"><div class="cover {{ ['c2','c4','c5','c10','c11'][$index % 5] }}">@if($item->cover_path)<img class="cover-img" src="{{ asset($item->cover_path) }}" alt="Иллюстрация к сказке «{{ $item->title }}" loading="lazy">@endif<span class="mini-title">{{ $item->title }}</span></div><h3>{{ $item->title }}</h3><div class="author">{{ $item->author ?: 'СказкоМир' }}</div></a>@endforeach</div></section>@endif
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Book', 'name' => $story->title, 'author' => ['@type' => 'Person', 'name' => $story->author ?: 'СказкоМир'], 'genre' => $story->genre, 'description' => $story->summary, 'url' => route('stories.show', $story)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
