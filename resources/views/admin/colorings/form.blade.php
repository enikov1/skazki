@extends('layouts.app')
@section('title', 'Изменить раскраску — панель управления')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки">
  <a href="{{ route('admin.dashboard') }}">Панель управления</a><span aria-hidden="true">/</span>
  <a href="{{ route('admin.colorings.pages.index') }}">Раскраски</a><span aria-hidden="true">/</span>
  <span>{{ $coloring->title }}</span>
</nav>
<div class="admin-topline">
  <div><p class="eyebrow">Мастерская</p><h1>Изменить раскраску</h1><p class="lead">Обновите карточку, категорию, контур и видимость рисунка.</p></div>
  <a class="button button-soft" href="{{ route('admin.colorings.pages.index') }}">К списку рисунков</a>
</div>
<section class="admin-panel">
  <form method="post" action="{{ route('admin.colorings.pages.update', $coloring) }}" data-ajax-form class="form-grid">
    @csrf @method('put')
    <label class="field"><span>Категория</span><select name="category_id" required>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $coloring->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <label class="field"><span>Название</span><input name="title" value="{{ old('title', $coloring->title) }}" maxlength="160" required></label>
    <label class="field"><span>Адрес страницы</span><input name="slug" value="{{ old('slug', $coloring->slug) }}" maxlength="180" placeholder="оставьте текущий адрес без изменений"></label>
    <label class="field"><span>Путь к превью</span><input name="preview_path" value="{{ old('preview_path', $coloring->preview_path) }}" maxlength="255" placeholder="storage/media/..." id="coloringPreview"><a class="text-link" href="{{ route('admin.media.index', ['select' => 'coloringPreview']) }}" data-media-picker>Выбрать превью</a></label>
    <label class="field"><span>Путь к контуру PNG</span><input name="outline_path" value="{{ old('outline_path', $coloring->outline_path) }}" maxlength="255" required placeholder="storage/media/..." id="coloringOutline"><a class="text-link" href="{{ route('admin.media.index', ['select' => 'coloringOutline']) }}" data-media-picker>Выбрать контур</a></label>
    <label class="field field-permission"><input type="hidden" name="is_published" value="0"><span><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $coloring->is_published))>Опубликовать</span></label>
    <div class="field full"><button class="button" type="submit">Сохранить изменения</button></div>
  </form>
</section>
@endsection
