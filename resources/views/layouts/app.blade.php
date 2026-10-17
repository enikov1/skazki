<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="@yield('description', 'Добрые сказки для чтения и прослушивания всей семьёй.')">
  <meta name="robots" content="@yield('robots', (! config('site.indexable') || request()->routeIs('admin.*', 'profile.*', 'login', 'register', 'password.*')) ? 'noindex,nofollow' : 'index,follow')">
  <link rel="canonical" href="{{ url()->current() }}">
  <meta property="og:site_name" content="СказкоМир">
  <meta property="og:title" content="@yield('title', 'СказкоМир — сказки для всей семьи')">
  <meta property="og:description" content="@yield('description', 'Добрые сказки для чтения и прослушивания всей семьёй.')">
  <meta property="og:type" content="@yield('og_type', 'website')">
  <meta name="theme-color" content="#fffaf1">
  <title>@yield('title', 'СказкоМир — сказки для всей семьи')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ \App\Support\AssetUrl::versioned('css/site.css') }}">
  <link rel="stylesheet" href="{{ \App\Support\AssetUrl::versioned('css/ui.css') }}">
  @if(request()->routeIs('profile.*'))<link rel="stylesheet" href="{{ \App\Support\AssetUrl::versioned('css/profile.css') }}">@endif
  @if(request()->routeIs('stories.read'))<link rel="stylesheet" href="{{ \App\Support\AssetUrl::versioned('css/reader.css') }}">@endif
  @if(request()->routeIs('admin.*'))<link rel="stylesheet" href="{{ \App\Support\AssetUrl::versioned('css/admin.css') }}"><link rel="stylesheet" href="{{ \App\Support\AssetUrl::versioned('vendor/quill/quill.snow.css') }}">@endif
  @stack('head')
</head>
<body data-site-page="@yield('site_page', 'laravel')">
<a class="skip-link" href="#main-content">Перейти к содержимому</a>
<header class="topbar"><div class="top-inner">
  <a class="brand" href="{{ route('home') }}"><span class="brand-icon"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bear"></use></svg></span><span>СказкоМир<small>ЧИТАЕМ ВМЕСТЕ</small></span></a>
  <nav class="nav" id="nav" aria-label="Основная навигация"><a href="{{ route('home') }}" @class(['active' => request()->routeIs('home')])>Главная</a><a href="{{ route('stories.index') }}" @class(['active' => request()->routeIs('stories.index', 'stories.show', 'stories.read')])>Все сказки</a><a href="{{ route('games.index') }}" @class(['active' => request()->routeIs('games.*', 'stories.quiz')])>Игры</a><a href="{{ route('audio.index') }}" @class(['active' => request()->routeIs('audio.*')])>Аудио</a><a href="{{ route('colorings.index') }}" @class(['active' => request()->routeIs('colorings.*')])>Раскраски</a><a href="{{ route('articles.index') }}" @class(['active' => request()->routeIs('articles.*')])>Родителям</a></nav>
  <button class="menu-close" type="button" data-menu-close aria-label="Закрыть меню"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#close"></use></svg>Закрыть меню</button>
  <div class="right-head">
  @auth
    @if(auth()->user()->hasAnyRole(['admin','moderator','editor']))<a class="icon-btn" href="{{ route('admin.dashboard') }}">Панель управления</a>@endif
    <a class="icon-btn" href="{{ route('profile.show') }}" aria-label="Личный кабинет"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#user"></use></svg>{{ auth()->user()->name }}</a><form method="post" action="{{ route('logout') }}" data-ajax-form>@csrf<button class="icon-btn" type="submit">Выйти</button></form>
  @else
    <a class="icon-btn" href="{{ route('login') }}" aria-label="Войти или зарегистрироваться"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#user"></use></svg>Войти</a>
  @endauth
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Открыть меню" aria-expanded="false" aria-controls="nav"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#menu"></use></svg></button>
  </div></header>
<div class="nav-scrim" id="navScrim"></div>
@unless(request()->routeIs('admin.*'))<div class="ticker" aria-label="Популярные сказки"><div class="ticker-in"><span class="ticker-label"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#star"></use></svg> Сегодня читают</span>@foreach(\App\Models\Story::published()->orderByDesc('updated_at')->limit(6)->get() as $tickerStory)<a class="ticker-book" href="{{ route('stories.show',$tickerStory) }}">@if($tickerStory->cover_path)<img src="{{ asset($tickerStory->cover_path) }}" alt="" loading="lazy">@endif<span><b>{{ $tickerStory->title }}</b><small>{{ $tickerStory->author ?: 'СказкоМир' }}</small></span></a>@endforeach</div></div>@endunless
<main id="main-content" tabindex="-1" class="page-wrap @if(request()->routeIs('admin.*')) admin-area @endif @if(request()->routeIs('audio.index')) audio-page @endif @if(request()->routeIs('login','register','password.*','verification.notice')) auth-page @endif">
  @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
  @yield('content')
</main>
<footer class="footer"><div class="footer-in"><div><a class="brand" href="{{ route('home') }}"><span class="brand-icon"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bear"></use></svg></span><span>СказкоМир<small>ЧИТАЕМ ВМЕСТЕ</small></span></a><p class="footer-about">Добрые истории, аудиосказки и занятия для детей и родителей.<br><br>Иллюстрации к книгам — с <a href="https://mishka-knizhka.ru/" target="_blank" rel="noopener">сайта «Мишкины книжки»</a>.</p></div><nav class="footer-links" aria-label="Ссылки в подвале"><a href="{{ route('stories.index') }}">Все сказки</a><a href="{{ route('audio.index') }}">Аудиосказки</a><a href="{{ route('games.index') }}">Игры и викторины</a><a href="{{ route('colorings.index') }}">Раскраски</a><a href="{{ route('colorings.gallery') }}">Галерея детских работ</a><a href="{{ route('articles.index') }}">Родителям</a><a href="{{ route('privacy-policy') }}">Политика конфиденциальности</a></nav></div><div class="copyright">© {{ date('Y') }} СказкоМир · Читаем вместе</div></footer>
<button class="sticky-top" id="toTop" type="button" aria-label="Наверх"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-up"></use></svg></button>
<div class="toast" id="toast" role="status" aria-live="polite"></div>
<div class="modal" id="modal"><div class="modal-card"><button class="modal-close" id="modalClose" type="button" aria-label="Закрыть"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#close"></use></svg></button><div id="modalContent"></div></div></div>
@if(request()->routeIs('admin.*'))<script src="{{ \App\Support\AssetUrl::versioned('vendor/quill/quill.js') }}" defer></script>@endif
<script src="{{ \App\Support\AssetUrl::versioned('js/app.js') }}" defer></script>
</body>
</html>
