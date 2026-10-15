@extends('layouts.app')
@section('title', 'Медиатека — СказкоМир')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><span>Медиатека</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Редакционная работа</p><h1>Медиатека</h1><p class="lead">Выбирайте готовые обложки и иллюстрации или загружайте свои изображения.</p></div></div>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="page-panel media-upload-panel"><div><h2>Загрузить изображение</h2><p>JPG, PNG или WebP до 8 МБ. Загруженные файлы сохраняются в общей медиатеке.</p></div><form method="post" action="{{ route('admin.media.store', request()->only('select')) }}" enctype="multipart/form-data">@csrf<input type="hidden" name="select" value="{{ $select }}"><label class="field"><span>Файл изображения</span><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label><button class="button" type="submit">Загрузить</button></form></section>
<section class="media-library-section"><div class="section-heading"><div><p class="eyebrow">{{ $media->count() }} файлов</p><h2>Доступные изображения</h2></div></div>
@if($media->isNotEmpty())<div class="media-grid">@foreach($media as $item)<article class="media-card"><a class="media-preview" href="{{ asset($item['path']) }}" target="_blank" rel="noopener"><img src="{{ asset($item['path']) }}" alt="" loading="lazy"></a><div class="media-card-info"><strong title="{{ $item['name'] }}">{{ $item['name'] }}</strong><code>{{ $item['path'] }}</code>@if($select)<button class="button button-small" type="button" data-media-select data-media-field="{{ $select }}" data-media-path="{{ $item['path'] }}">Выбрать изображение</button>@else<a class="text-link" href="{{ asset($item['path']) }}" target="_blank" rel="noopener">Открыть</a>@endif</div></article>@endforeach</div>@else<div class="empty-state"><span aria-hidden="true">🖼️</span><h2>Изображений пока нет</h2><p>Загрузите первое изображение, чтобы использовать его в материалах.</p></div>@endif
</section>
@endsection
