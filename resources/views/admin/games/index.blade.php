@extends('layouts.app')
@section('title', 'Игры — Панель управления')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><span>Игры</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Игровой раздел</p><h1>Конструктор игр</h1><p class="lead">Викторины редактируются в карточке сказки. Здесь создаются «Найди отличия» и мозаики.</p></div><a class="button" href="{{ route('admin.games.create') }}">Создать игру</a></div>
<section class="admin-panel"><div class="table-wrap"><table><thead><tr><th>Игра</th><th>Тип</th><th>Состояние</th><th>Действия</th></tr></thead><tbody>
@forelse($games as $game)<tr><td><strong>{{ $game->title }}</strong><br><small>/igry/{{ $game->type === 'differences' ? 'naydi-otlichiya' : 'mozaiki' }}/{{ $game->slug }}</small><br><small>Автор: {{ $game->creator?->name ?? 'не указан' }} · Изменил: {{ $game->updater?->name ?? 'не указан' }}</small></td><td>{{ $game->type_label }}</td><td>{{ $game->is_published ? 'Опубликована' : 'Черновик' }}</td><td><a class="button button-small button-soft" href="{{ route('admin.games.edit', $game) }}">Изменить</a> @if($game->is_published)<a class="button button-small button-quiet" href="{{ $game->type === 'differences' ? route('games.differences.show', $game->slug) : route('games.mosaics.show', $game->slug) }}">Открыть</a>@endif</td></tr>@empty<tr><td colspan="4"><div class="empty-state"><h2>Игр пока нет</h2><p>Создайте первую мозаику или игру «Найди отличия».</p></div></td></tr>@endforelse
</tbody></table></div>{{ $games->links('vendor.pagination.skazki') }}</section>
@endsection
