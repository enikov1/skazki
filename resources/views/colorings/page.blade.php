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
      <section class="coloring-tool-section"><h2>Размер кисти</h2><label class="coloring-size"><span class="visually-hidden">Размер кисти</span><input type="range" min="6" max="72" value="24" data-coloring-size><output data-coloring-size-value>24</output></label><div class="coloring-size-preview" aria-hidden="true"><span data-coloring-size-preview></span><small>Пример размера</small></div></section>
      <section class="coloring-tool-section"><div class="coloring-palette-heading"><h2>Цвет</h2><label class="coloring-custom-color"><span>Свой цвет</span><input type="color" value="#f37451" data-coloring-color aria-label="Выбрать свой цвет"></label></div><div class="coloring-palette" role="group" aria-label="Палитра цветов">@foreach(['#f37451','#f4c768','#71a97f','#77bfe3','#9b8ad5','#f39ab1','#263d4b','#ffffff'] as $color)<button type="button" class="color-swatch" style="--swatch:{{ $color }}" data-coloring-swatch="{{ $color }}" aria-label="Цвет {{ $color }}"></button>@endforeach</div></section>
    </aside>
  </section>

  <section class="coloring-community-grid" aria-label="Поделиться рисунком и поставить оценку">
    <section class="coloring-submission-form" aria-labelledby="coloring-share-title">
      <div><p class="eyebrow">Галерея сообщества</p><h2 id="coloring-share-title">Поделиться работой</h2><p>Родитель может отправить рисунок в галерею. Перед публикацией работу проверит модератор.</p></div>
      @auth<form method="post" action="{{ route('colorings.submissions.store', [$category, $page]) }}" enctype="multipart/form-data" data-ajax-form data-coloring-submission data-ajax-clear>@csrf
        <label class="field"><span>Название работы</span><input name="title" maxlength="120" required value="{{ $page->title }} — моя раскраска"></label>
        <label class="field"><span>PNG-файл рисунка <small>(можно не выбирать — отправим холст)</small></span><input type="file" name="artwork" accept="image/png"></label>
        <label class="consent-check"><input type="checkbox" name="guardian_consent" value="1" required><span>Я являюсь родителем или законным представителем ребёнка, разрешаю показать эту работу в публичной галерее и подтверждаю, что на изображении нет личных данных.</span></label>
        <button class="button" type="button" data-coloring-share>Отправить на проверку</button>
      </form>@else<p class="coloring-login-note"><a href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">Войдите в аккаунт</a>, чтобы родитель мог отправить работу в галерею.</p>@endauth
    </section>
    <section class="coloring-rating-card" aria-labelledby="coloring-rating-title">
      <p class="eyebrow">Ваше мнение</p><h2 id="coloring-rating-title">Оцените рисунок</h2>
      <form method="post" action="{{ route('colorings.rate', [$category, $page]) }}" data-ajax-form data-coloring-rating>@csrf<fieldset><legend class="visually-hidden">Ваша оценка от 1 до 5</legend><div class="rating-options">@foreach(range(5, 1) as $score)<label><input type="radio" name="rating" value="{{ $score }}" @checked((int) $userRating === $score) required><span aria-label="{{ $score }} из 5">★</span></label>@endforeach</div><button class="button button-small" type="submit">Сохранить оценку</button></fieldset></form>
    </section>
  </section>
</div>

<section class="page-panel coloring-related-section" aria-labelledby="coloring-related-title">
  <div class="coloring-related-heading"><div><p class="eyebrow">Продолжайте рисовать</p><h2 id="coloring-related-title">Другие раскраски</h2></div><a class="text-link" href="{{ route('colorings.index') }}">Все раскраски <span aria-hidden="true">→</span></a></div>
  @if($related->isNotEmpty())<div class="coloring-page-grid coloring-related-grid">@foreach($related as $other)<article class="coloring-page-card"><a href="{{ route('colorings.page', [$other->category, $other]) }}"><span>@if($other->preview_path)<img src="{{ asset($other->preview_path) }}" alt="Контур «{{ $other->title }}»" loading="lazy">@else<svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#pencil"></use></svg>@endif</span><strong>{{ $other->title }}</strong><small>{{ $other->category?->name ?? 'Раскраски' }}</small></a></article>@endforeach</div>
  @else<div class="empty-state"><p>Пока нет других раскрасок. Загляните в каталог.</p></div>@endif
</section>

<section class="page-panel coloring-page-comments story-comments" id="comments" aria-labelledby="coloring-comments-title">
  <h2 id="coloring-comments-title">Комментарии</h2><p class="notice">Комментарии появятся после проверки модератором. Не публикуйте личные данные.</p>
  <form class="form-grid" method="post" action="{{ route('colorings.comments.store', [$category, $page]) }}" data-ajax-form data-ajax-clear>@csrf@guest<label class="field full"><span>Как вас называть?</span><input type="text" name="guest_name" minlength="2" maxlength="40" required autocomplete="nickname"><small class="form-hint">Можно указать псевдоним — фамилия и почта не нужны.</small></label>@endguest<label class="field full"><span>Ваш комментарий</span><textarea name="body" rows="3" minlength="3" maxlength="1500" required placeholder="Поделитесь впечатлением о раскраске"></textarea></label><div class="field full"><button class="button" type="submit">Отправить на проверку</button></div></form>
  <div class="comment-list">@forelse($comments as $comment)<article class="comment"><div class="comment-heading"><b>Читатель</b><time datetime="{{ $comment->created_at->toAtomString() }}">{{ $comment->created_at->translatedFormat('j F Y') }}</time></div><p>{{ $comment->body }}</p></article>@empty<div class="empty"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#message"></use></svg><p>Пока нет опубликованных комментариев.</p></div>@endforelse</div>
  {{ $comments->links('vendor.pagination.skazki') }}
</section>
@endsection
