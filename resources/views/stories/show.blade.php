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
<section class="article-rating" id="story-rating" aria-label="Оценка сказки" data-print-hide><div class="rating-score"><span class="rating-star" aria-hidden="true">★</span><strong>{{ $rating->total ? number_format((float) $rating->average, 1, ',', ' ') : '—' }}</strong><span>{{ $rating->total ? $rating->total.' '.trans_choice('оценка|оценки|оценок', $rating->total) : 'Пока без оценок' }}</span></div><form method="post" action="{{ route('stories.rate', $story) }}" data-ajax-form class="rating-form">@csrf<fieldset><legend>{{ $userRating ? 'Ваша оценка: '.$userRating.' из 5' : 'Оцените сказку' }}</legend><div class="rating-options">@foreach(range(5, 1) as $score)<label><input type="radio" name="rating" value="{{ $score }}" @checked((int) old('rating', $userRating) === $score) required><span aria-label="{{ $score }} из 5">★</span></label>@endforeach</div><button class="button button-small" type="submit">{{ $userRating ? 'Изменить оценку' : 'Оценить' }}</button></fieldset>@error('rating')<p class="field-error">{{ $message }}</p>@enderror</form></section>
<section class="story-description story-detail" data-print-area="story"><h2>О сказке</h2><p>{{ $story->summary }}</p><p>Автор: {{ $story->author ?: 'СказкоМир' }}. Категория: {{ $story->genre }}. Подходит для детей {{ $story->age_min }}–{{ $story->age_max }} лет.</p><article class="reading-text reader-theme-{{ $readerTheme }}" style="font-size: {{ $readerFontSize }}px; --reader-max-width: {{ $readerWidth }}px">{!! app(\App\Support\BBEditor::class)->render($story->body) !!}</article></section>
<section class="page-panel story-comments" id="comments" data-print-hide><h2>Отзывы о сказке</h2><p class="notice">Поделитесь впечатлением о сказке — ваш отзыв появится после проверки.</p>
<form class="form-grid" method="post" action="{{ route('comments.store', $story) }}" data-ajax-form data-ajax-clear>@csrf@guest<label class="field full"><span>Как вас называть?</span><input type="text" name="guest_name" minlength="2" maxlength="40" required autocomplete="nickname"><small class="form-hint">Можно указать псевдоним — фамилия и почта не нужны.</small></label>@endguest<label class="field full"><span>Комментарий</span><textarea name="body" rows="4" minlength="3" maxlength="2000" required placeholder="Что вам запомнилось?"></textarea></label>@error('body')<p class="field-error">{{ $message }}</p>@enderror@error('guest_name')<p class="field-error">{{ $message }}</p>@enderror<div class="field full"><button class="btn" type="submit">Оставить отзыв</button></div></form>
<div class="comment-list">@forelse($comments as $comment)<article class="comment" id="comment-{{ $comment->id }}"><div class="comment-heading"><b>{{ ($comment->user?->name ?? $comment->guest_name ?? 'Гость') }}</b><time datetime="{{ $comment->created_at->toAtomString() }}">{{ $comment->created_at->translatedFormat('j F Y') }}</time></div><p>{{ $comment->body }}</p><div class="laravel-comment-actions">@foreach(['like'=>'Нравится','dislike'=>'Не нравится'] as $vote=>$label)<form method="post" action="{{ route('comments.vote', $comment) }}" data-ajax-form>@csrf<input type="hidden" name="vote" value="{{ $vote }}"><button class="pill" type="submit">{{ $label }} · {{ $vote === 'like' ? $comment->likes_count : $comment->dislikes_count }}</button></form>@endforeach</div></article>@empty<div class="empty"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#message"></use></svg><p>Пока нет отзывов. Поделитесь первым впечатлением.</p></div>@endforelse</div>{{ $comments->links('vendor.pagination.skazki') }}</section>
@if($story->quizQuestions()->exists())<section class="quiz-callout"><span class="quiz-mark"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bulb"></use></svg></span><div><span class="eyebrow">Проверьте, что запомнилось</span><h2>Викторина по сказке «{{ $story->title }}»</h2><p>Короткие вопросы о героях и событиях истории — пройдите после чтения.</p></div><a class="btn" href="{{ route('stories.quiz', $story) }}">Пройти викторину <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></section>@endif
@if($related->isNotEmpty())<section class="page-panel related-section"><div class="section-head"><div><span class="eyebrow">Продолжите чтение</span><h2>Другие сказки</h2></div><a href="{{ route('stories.index', ['genre' => $story->genre]) }}">Вся библиотека <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></div><div class="book-row">@foreach($related as $index => $item)<a class="book-card" href="{{ route('stories.show', $item) }}"><div class="cover {{ ['c2','c4','c5','c10','c11'][$index % 5] }}">@if($item->cover_path)<img class="cover-img" src="{{ asset($item->cover_path) }}" alt="Иллюстрация к сказке «{{ $item->title }}" loading="lazy">@endif<span class="mini-title">{{ $item->title }}</span></div><h3>{{ $item->title }}</h3><div class="author">{{ $item->author ?: 'СказкоМир' }}</div></a>@endforeach</div></section>@endif
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'Book', 'name' => $story->title, 'author' => ['@type' => 'Person', 'name' => $story->author ?: 'СказкоМир'], 'genre' => $story->genre, 'description' => $story->summary, 'url' => route('stories.show', $story)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection
