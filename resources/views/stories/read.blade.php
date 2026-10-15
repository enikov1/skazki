@extends('layouts.app')
@section('title', 'Читать «'.$story->title.'» — СказкоМир')
@section('description', $story->seo_description ?: $story->summary)
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('stories.show', $story) }}">{{ $story->title }}</a><span>/</span><span aria-current="page">Чтение</span></nav>
<main class="reading-shell laravel-reader" data-reader data-story="{{ $story->slug }}" data-resume="{{ $paragraphPosition }}" @auth data-progress-url="{{ route('stories.reading-progress', $story) }}" data-preferences-url="{{ route('profile.preferences') }}" @endauth>
  <div class="reader-toolbar" aria-label="Настройки чтения">
    <a class="pill" href="{{ route('stories.show', $story) }}">← К сказке</a>
    <label>Буквы <select data-reader-font aria-label="Размер текста"><option value="16" @selected($readerFontSize === 16)>Небольшой</option><option value="18" @selected($readerFontSize === 18)>Меньше</option><option value="20" @selected($readerFontSize === 20)>Обычный</option><option value="22" @selected($readerFontSize === 22)>Крупный</option><option value="24" @selected($readerFontSize === 24)>Очень крупный</option></select></label>
    <label>Страница <select data-reader-theme aria-label="Цвет страницы"><option value="cream" @selected($readerTheme === 'cream')>Тёплая</option><option value="white" @selected($readerTheme === 'white')>Белая</option><option value="green" @selected($readerTheme === 'green')>Зелёная</option></select></label>
    <label>Ширина <select data-reader-width aria-label="Ширина текста"><option value="narrow" @selected($readerWidth === 560)>Узкая</option><option value="medium" @selected($readerWidth === 700)>Средняя</option><option value="wide" @selected($readerWidth === 900)>Широкая</option></select></label>
    <button class="pill" type="button" data-reader-top>В начало</button>
  </div>
  <div class="reading-progress" data-reader-status role="status" aria-live="polite">Подготавливаем сказку…</div>
  <article class="reader-text reader-theme-{{ $readerTheme }}" style="--reader-size: {{ $readerFontSize }}px; --reader-max-width: {{ $readerWidth }}px" data-reader-text>
    <h1 class="reader-title">{{ $story->title }}</h1>
    <p class="reader-author">{{ $story->author ?: 'СказкоМир' }}</p>
    @php($readerBlocks = preg_split('/\n\s*\n/', trim($story->body ?? '')) ?: [])
    @forelse($readerBlocks as $index => $block)<div class="reader-block" data-reader-block="{{ $index }}">{!! app(\App\Support\BBEditor::class)->render($block) !!}</div>@empty<div class="reader-block" data-reader-block="0">{!! app(\App\Support\BBEditor::class)->render($story->summary) !!}</div>@endforelse
    <p class="reader-end">Конец сказки</p>
  </article>
</main>
@endsection
