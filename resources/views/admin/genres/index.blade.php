@extends('layouts.app')
@section('title', 'Жанры — управление сайтом')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><span>Жанры</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Справочники библиотеки</p><h1>Жанры сказок</h1><p class="lead">Список жанров используется в редакторе сказок и фильтрах каталога.</p></div></div>
@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<section class="admin-panel genre-create-panel"><div><h2>Добавить жанр</h2><p class="form-hint">Новый жанр сразу появится в списке выбора материалов.</p></div>
    <form class="genre-create-form" method="post" action="{{ route('admin.genres.store') }}" data-ajax-form data-genre-create>@csrf
        <label class="field"><span>Название жанра</span><input name="name" maxlength="80" required placeholder="Например, Сказки о животных"></label>
        <button class="button" type="submit">Добавить жанр</button>
    </form>
</section>
<section class="admin-panel genre-list-panel">
    <div class="admin-panel-heading"><div><p class="eyebrow"><span data-genre-count>{{ $genres->count() }}</span> жанров</p><h2>Доступные жанры</h2></div></div>
    <div class="genre-list" data-genre-list>
        @foreach($genres as $genre)
            <article class="genre-row" data-genre-row data-genre-id="{{ $genre->id }}">
                <div class="genre-row-copy"><strong>{{ $genre->name }}</strong><span>{{ $genre->stories_count }} {{ trans_choice('сказка|сказки|сказок', $genre->stories_count) }}</span></div>
                <form class="genre-edit-form" method="post" action="{{ route('admin.genres.update', $genre) }}" data-ajax-form data-genre-edit>@csrf @method('patch')
                    <label class="field"><span class="sr-only">Название жанра</span><input name="name" value="{{ $genre->name }}" maxlength="80" required></label>
                    <button class="button button-soft button-small" type="submit">Сохранить</button>
                </form>
                <form method="post" action="{{ route('admin.genres.destroy', $genre) }}" data-ajax-form data-genre-delete>@csrf @method('delete')
                    <button class="button button-danger button-small" type="submit" @disabled($genre->stories_count > 0) title="{{ $genre->stories_count > 0 ? 'Сначала переназначьте сказки этого жанра' : 'Удалить жанр' }}">Удалить</button>
                </form>
            </article>
        @endforeach
    </div>
    @if($genres->isEmpty())<div class="empty-state"><span aria-hidden="true">📚</span><h2>Жанров пока нет</h2><p>Добавьте первый жанр выше.</p></div>@endif
</section>
@endsection
