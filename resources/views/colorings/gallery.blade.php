@extends('layouts.app')
@section('title', 'Галерея детских работ — СказкоМир')
@section('description', 'Раскраски, опубликованные после подтверждения родителя и проверки модератором.')
@if(request()->query())@section('robots', 'noindex,follow')@endif
@push('head')
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($item, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item[0], 'item' => $item[1]], [['Главная', route('home')], ['Раскраски', route('colorings.index')], ['Галерея работ', route('colorings.gallery')]], array_keys([['Главная', route('home')], ['Раскраски', route('colorings.index')], ['Галерея работ', route('colorings.gallery')]]))], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('colorings.index') }}">Раскраски</a><span>/</span><span aria-current="page">Галерея работ</span></nav>
<header class="page-panel coloring-heading"><p class="eyebrow">Творчество сообщества</p><h1 class="page-title">Галерея работ</h1><p class="lead">Здесь размещены только рисунки, опубликованные после подтверждения родителя и проверки модератором.</p></header>
@if($works->isNotEmpty())<section class="coloring-page-grid">@foreach($works as $work)<article class="coloring-page-card"><a href="{{ route('colorings.gallery.work', $work) }}"><span><img src="{{ route('colorings.gallery.image', $work) }}" alt="Раскраска «{{ $work->title }}»" loading="lazy"></span><strong>{{ $work->title }}</strong></a></article>@endforeach</section>{{ $works->links('vendor.pagination.skazki') }}@else<section class="page-panel empty-state"><span aria-hidden="true">🖼️</span><h2>Галерея пока пуста</h2><p>Семейные работы появятся здесь после проверки родителем и модератором.</p><a class="button button-soft" href="{{ route('colorings.index') }}">Выбрать раскраску</a></section>@endif
@endsection
