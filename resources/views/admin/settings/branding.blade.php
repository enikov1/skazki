@extends('layouts.app')
@section('title', 'Логотип для печати — СказкоМир')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><span aria-current="page">Логотип для печати</span></nav>
<div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Административная навигация"><p class="eyebrow">Рабочее место</p><h2>Управление сайтом</h2><a class="admin-link" href="{{ route('admin.dashboard') }}">Обзор</a><a class="admin-link" href="{{ route('admin.stories.index') }}">Сказки</a><a class="admin-link" href="{{ route('admin.media.index') }}">Медиатека</a><a class="admin-link active" href="{{ route('admin.settings.branding.edit') }}">Логотип для печати</a></aside>
    <section class="admin-content"><div class="admin-topline"><div><p class="eyebrow">Настройки сайта</p><h1>Логотип-водяной знак</h1><p class="lead">Этот логотип будет печататься в правом нижнем углу каждой страницы сказки.</p></div></div>
      @if(session('status'))<p class="notice">{{ session('status') }}</p>@endif
      <section class="admin-panel"><form method="post" action="{{ route('admin.settings.branding.update') }}" enctype="multipart/form-data" data-ajax-form data-print-branding>
        @csrf @method('PUT')
        <label class="field"><span>Файл логотипа</span><input type="file" name="print_watermark_logo" accept="image/png,image/jpeg,image/webp" required><small class="field-help">PNG с прозрачным фоном выглядит лучше. Поддерживаются PNG, JPG и WebP до 4 МБ.</small></label>
        @error('print_watermark_logo')<p class="field-error">{{ $message }}</p>@enderror
        @if($logoUrl)<div class="admin-current-logo" data-print-logo-preview><span>Сейчас используется</span><img src="{{ $logoUrl }}" alt="Текущий логотип для печати"></div>@endif
        <button class="button button-primary" type="submit">Сохранить логотип</button>
      </form></section>
    </section>
</div>
@endsection
