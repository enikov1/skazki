@extends('layouts.reader')
@section('title', 'Читать «'.$story->title.'» — СказкоМир')
@section('robots', 'noindex,nofollow')
@section('content')
@include('stories.partials.print-sheet')
<div class="ebook-reader reading-shell laravel-reader" data-reader data-story="{{ $story->slug }}" data-resume="{{ $paragraphPosition }}" @auth data-progress-url="{{ route('stories.reading-progress', $story) }}" data-preferences-url="{{ route('profile.preferences') }}" @endauth>
  <header class="ebook-chrome" data-print-hide>
    <div class="ebook-header">
      <a class="ebook-back" href="{{ route('stories.show', $story) }}" aria-label="Вернуться к сказке"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-left"></use></svg><span>К сказке</span></a>
      <a class="ebook-brand" href="{{ route('home') }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bear"></use></svg><span>СказкоМир</span></a>
      <div class="ebook-current-title"><span>Сейчас читаем</span><strong>{{ $story->title }}</strong></div>
    </div>
    <nav class="ebook-toolbar" aria-label="Навигация по чтению">
      <div class="ebook-page-controls"><button class="button button-soft" type="button" data-reader-prev><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-left"></use></svg><span>Назад</span></button><button class="button button-soft" type="button" data-reader-next><span>Дальше</span><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></button></div>
      <div class="ebook-toolbar-actions"><button class="button button-quiet" type="button" data-reader-settings-toggle aria-expanded="false" aria-controls="readerSettings"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#settings"></use></svg><span>Настройки чтения</span></button><button class="button button-quiet" type="button" data-print-page><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#printer"></use></svg><span>Печать</span></button></div>
    </nav>
    <div class="ebook-status" role="status" aria-live="polite"><span data-reader-status>Подготавливаем сказку…</span><div class="ebook-progress-track" role="progressbar" aria-label="Прогресс чтения" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><span data-reader-progressbar></span></div></div>
    <section class="ebook-settings" id="readerSettings" data-reader-settings hidden aria-label="Настройки чтения">
      <label><span>Размер букв</span><select data-reader-font aria-label="Размер букв"><option value="16" @selected($readerFontSize === 16)>Небольшой</option><option value="18" @selected($readerFontSize === 18)>Меньше</option><option value="20" @selected($readerFontSize === 20)>Обычный</option><option value="22" @selected($readerFontSize === 22)>Крупный</option><option value="24" @selected($readerFontSize === 24)>Очень крупный</option></select></label>
      <label><span>Цвет страницы</span><select data-reader-theme aria-label="Цвет страницы"><option value="cream" @selected($readerTheme === 'cream')>Тёплая</option><option value="white" @selected($readerTheme === 'white')>Белая</option><option value="green" @selected($readerTheme === 'green')>Зелёная</option></select></label>
      <label><span>Ширина текста</span><select data-reader-width aria-label="Ширина текста"><option value="narrow" @selected($readerWidth === 560)>Узкая</option><option value="medium" @selected($readerWidth === 700)>Средняя</option><option value="wide" @selected($readerWidth === 900)>Широкая</option></select></label>
      <button class="button button-quiet button-small" type="button" data-reader-top>В начало</button>
    </section>
  </header>
  <article class="ebook-paper reader-text reader-theme-{{ $readerTheme }}" style="--reader-size: {{ $readerFontSize }}px; --reader-max-width: {{ $readerWidth }}px" data-reader-text>
    <div class="ebook-book-heading"><p class="eyebrow">{{ $story->genre }}</p><h1 class="reader-title">{{ $story->title }}</h1><p class="reader-author">{{ $story->author ?: 'СказкоМир' }}</p></div>
    @php($readerBlocks = preg_split('/\n\s*\n/', trim($story->body ?? '')) ?: [])
    @forelse($readerBlocks as $index => $block)<div class="reader-block" data-reader-block="{{ $index }}">{!! app(\App\Support\BBEditor::class)->render($block) !!}</div>@empty<div class="reader-block" data-reader-block="0">{!! app(\App\Support\BBEditor::class)->render($story->summary) !!}</div>@endforelse
    <p class="reader-end">Конец сказки</p>
  </article>
  <nav class="ebook-bottom-nav" aria-label="Продолжить чтение" data-print-hide><button class="button button-soft" type="button" data-reader-prev><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-left"></use></svg> Предыдущий фрагмент</button><a class="button button-quiet" href="{{ route('stories.show', $story) }}">Вернуться к сказке</a><button class="button" type="button" data-reader-next>Следующий фрагмент <svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></button></nav>
</div>
@endsection
