@extends('layouts.app')
@section('title', 'Управление сайтом — СказкоМир')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><span aria-current="page">Панель управления</span></nav>
<div class="admin-layout">
    <aside class="admin-sidebar" aria-label="Административная навигация">
        <p class="eyebrow">Рабочее место</p>
        <h2>Управление сайтом</h2>
        <a class="admin-link active" href="{{ route('admin.dashboard') }}">Обзор</a>
        @if(auth()->user()->hasAnyRole(['admin', 'editor']))
            <a class="admin-link" href="{{ route('admin.stories.index') }}">Сказки</a>
            <a class="admin-link" href="{{ route('admin.articles.index') }}">Статьи</a>
            <a class="admin-link" href="{{ route('admin.media.index') }}">Медиатека</a>
            <a class="admin-link" href="{{ route('admin.colorings.pages.index') }}">Раскраски</a>
        @endif
        @if(auth()->user()->hasRole('admin'))
            <a class="admin-link" href="{{ route('admin.genres.index') }}">Жанры</a>
            <a class="admin-link" href="{{ route('admin.coloring-categories.index') }}">Категории раскрасок</a>
        @endif
        @if(auth()->user()->hasAnyRole(['admin', 'moderator']))
            <a class="admin-link" href="{{ route('admin.moderation.index') }}">Комментарии @if($pending)<span class="badge">{{ $pending }}</span>@endif</a>
            <a class="admin-link" href="{{ route('admin.articles.comments') }}">Комментарии к статьям</a>
            <a class="admin-link" href="{{ route('admin.coloring-submissions.index') }}">Детские работы</a>
            <a class="admin-link" href="{{ route('admin.coloring-work-comments.index') }}">Комментарии к работам</a>
        @endif
        @if(auth()->user()->hasRole('admin'))
            <a class="admin-link" href="{{ route('admin.users.index') }}">Пользователи и роли</a>
            <a class="admin-link" href="{{ route('admin.audit-logs.index') }}">Журнал действий</a>
        @endif
    </aside>

    <section class="admin-content">
        <div class="admin-topline">
            <div><p class="eyebrow">Добро пожаловать, {{ auth()->user()->name }}</p><h1>Обзор</h1></div>
            @can('create', App\Models\Story::class)<a class="button" href="{{ route('admin.stories.create') }}">Добавить сказку</a>@endcan
        </div>
        <div class="stat-grid">
            <article class="stat-card"><span>Все сказки</span><strong>{{ $stories }}</strong><small>в библиотеке</small></article>
            <article class="stat-card"><span>Опубликовано</span><strong>{{ $published }}</strong><small>видны читателям</small></article>
            <article class="stat-card"><span>Ждут проверки</span><strong>{{ $pending }}</strong><small>комментарии</small></article>
            <article class="stat-card"><span>Пользователи</span><strong>{{ $users }}</strong><small>всего аккаунтов</small></article>
        </div>
        <section class="admin-panel">
            <div class="admin-panel-heading">
                <div><p class="eyebrow">Последние изменения</p><h2>Сказки</h2></div>
                @can('create', App\Models\Story::class)<a class="text-link" href="{{ route('admin.stories.index') }}">Все сказки →</a>@endcan
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Название</th><th>Автор</th><th>Статус</th><th><span class="sr-only">Действие</span></th></tr></thead>
                    <tbody>
                    @forelse($recentStories as $story)
                        <tr><td><strong>{{ $story->title }}</strong></td><td>{{ $story->author ?: '—' }}</td><td><span class="status-pill status-{{ $story->status }}">{{ \App\Enums\EditorialStatus::labelFor($story->status) }}</span></td><td>@can('update', $story)<a class="button button-soft button-small" href="{{ route('admin.stories.edit', $story) }}">Открыть</a>@endcan</td></tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state"><h2>Материалов пока нет</h2><p>Добавьте первую сказку в библиотеку.</p></div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</div>
@endsection
