@extends('layouts.app')
@section('title', 'Предпросмотр: '.$article->title.' — СказкоМир')
@section('description', $article->seo_description ?: $article->summary)
@section('robots', 'noindex,nofollow')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><a href="{{ route('admin.articles.edit', $article) }}">Редактирование статьи</a><span>/</span><span>Предпросмотр</span></nav>
<div class="preview-banner" role="status"><strong>Предпросмотр · {{ $statusLabel }}</strong><span>Страница доступна редакторам материала и не попадёт в поиск.</span></div>
<article class="article-detail"><header class="article-detail-head"><div class="article-meta"><span>{{ $article->category }}</span><span>{{ $article->reading_minutes }} мин чтения</span><span>{{ $statusLabel }}</span></div><h1>{{ $article->title }}</h1><p class="article-deck">{{ $article->summary }}</p>@if($article->creator)<p class="article-byline">Автор: {{ $article->creator->name }}</p>@endif</header>
@if($article->cover_path)<img class="article-detail-cover" src="{{ asset($article->cover_path) }}" alt="Иллюстрация к статье «{{ $article->title }}»" width="1200" height="650">@endif
<div class="article-reading-text">{!! app(\App\Support\BBEditor::class)->render($article->body) !!}</div></article>
<a class="button button-soft" href="{{ route('admin.articles.edit', $article) }}">Вернуться к редактированию</a>
@endsection
