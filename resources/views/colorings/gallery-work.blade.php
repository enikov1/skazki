@extends('layouts.app')
@section('title', $submission->title.' — галерея работ — СказкоМир')
@section('description', 'Работа из галереи раскрасок СказкоМира.')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('colorings.index') }}">Раскраски</a><span>/</span><a href="{{ route('colorings.gallery') }}">Галерея работ</a><span>/</span><span aria-current="page">{{ $submission->title }}</span></nav>
<main class="coloring-gallery-work">
    <article class="page-panel coloring-work-detail">
        <p class="eyebrow" data-print-hide>Работа сообщества · {{ $submission->created_at->format('d.m.Y') }}</p>
        <h1 class="page-title">{{ $submission->title }}</h1>
        <figure class="coloring-work-image" data-print-area="artwork"><img src="{{ route('colorings.gallery.image', $submission) }}" alt="Работа «{{ $submission->title }}»"></figure>
        <div class="coloring-work-actions" data-print-hide>
            @auth
                <form method="post" action="{{ route('colorings.gallery.like', $submission->slug) }}" data-ajax-form data-coloring-work-like>@csrf<button class="button {{ $liked ? '' : 'button-soft' }}" type="submit" aria-pressed="{{ $liked ? 'true' : 'false' }}">Нравится · <span data-like-count>{{ $likesCount }}</span></button></form>
            @else
                <a class="button button-soft" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">Нравится · {{ $likesCount }}</a>
            @endauth
            <button class="button button-quiet" type="button" data-copy-link>Скопировать ссылку</button>
            <button class="button button-quiet" type="button" data-print-page><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#printer"></use></svg> Распечатать работу</button>
            <a class="button button-quiet" href="{{ route('colorings.gallery') }}">К галерее</a>
        </div>
    </article>
    <section class="page-panel coloring-comments" aria-labelledby="comments-title" data-print-hide>
        <h2 id="comments-title">Комментарии</h2>
        <p class="form-hint">Комментарии появляются после проверки. Имена авторов не показываются.</p>
        @auth
            <form class="coloring-comment-form" method="post" action="{{ route('colorings.gallery.comment', $submission->slug) }}" data-ajax-form data-coloring-work-comment>@csrf
                <label class="field"><span>Ваш комментарий</span><textarea name="body" rows="3" minlength="3" maxlength="1500" required placeholder="Поделитесь добрыми словами"></textarea></label>
                <button class="button" type="submit">Отправить на проверку</button>
            </form>
        @else
            <p><a class="text-link" href="{{ route('login', ['redirect' => request()->fullUrl()]) }}">Войдите</a>, чтобы оставить комментарий.</p>
        @endauth
        <div class="coloring-comment-list">
            @forelse($comments as $comment)<article class="coloring-comment"><strong>Читатель</strong><time datetime="{{ $comment->created_at->toAtomString() }}">{{ $comment->created_at->format('d.m.Y') }}</time><p>{{ $comment->body }}</p></article>
            @empty<p class="form-hint">Пока нет опубликованных комментариев.</p>@endforelse
        </div>
        {{ $comments->links('vendor.pagination.skazki') }}
    </section>
</main>
@endsection
