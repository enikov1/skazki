@extends('layouts.app')
@section('title', $page->seo_title ?: $page->title.' — СказкоМир')
@section('description', $page->seo_description ?: \Illuminate\Support\Str::limit(strip_tags(preg_replace('/\[[^\]]+\]/', ' ', $page->description ?: $page->title)), 160))
@section('og_type', 'website')
@push('head')
<script type="application/ld+json">{!! json_encode([chr(64).'context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => route('home')], ['@type' => 'ListItem', 'position' => 2, 'name' => in_array($page->type, ['audio_genre', 'audio_age'], true) ? 'Аудиосказки' : 'Все сказки', 'item' => in_array($page->type, ['audio_genre', 'audio_age'], true) ? route('audio.index') : route('stories.index')], ['@type' => 'ListItem', 'position' => 3, 'name' => $page->title, 'item' => $page->publicUrl()]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ in_array($page->type, ['audio_genre', 'audio_age'], true) ? route('audio.index') : route('stories.index') }}">{{ in_array($page->type, ['audio_genre', 'audio_age'], true) ? 'Аудиосказки' : 'Все сказки' }}</a><span>/</span><span aria-current="page">{{ $page->title }}</span></nav>
<header class="catalog-landing-heading">
  @if($page->cover_path)<img class="catalog-landing-cover" src="{{ asset($page->cover_path) }}" alt="Иллюстрация: {{ $page->title }}">@endif
  <div><p class="eyebrow">{{ match($page->type) { 'story_genre' => 'Жанр сказок', 'audio_genre' => 'Жанр аудиосказок', 'author' => 'Автор сказок', 'age' => 'Сказки по возрасту', 'audio_age' => 'Аудиосказки по возрасту' } }}</p><h1>{{ $page->title }}</h1><p class="catalog-landing-count">{{ $stories->total() }} {{ trans_choice('материал|материала|материалов', $stories->total()) }} в подборке</p></div>
</header>
@if(filled($page->description))<section class="catalog-landing-description" aria-label="Описание подборки">{!! app(\App\Support\BBEditor::class)->render($page->description) !!}</section>@endif
<section class="catalog-landing-results"><div class="section-heading"><h2>{{ in_array($page->type, ['audio_genre', 'audio_age'], true) ? 'Аудиосказки' : 'Сказки' }}</h2></div>@include('stories.catalog-results', ['stories' => $stories])</section>
@endsection
