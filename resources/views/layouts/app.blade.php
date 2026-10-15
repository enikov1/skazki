<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="@yield('description', 'Добрые сказки для чтения и прослушивания всей семьёй.')">
  <meta name="robots" content="@yield('robots', 'index,follow')">
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
  <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=9">
  @stack('head')
</head>
<body data-site-page="laravel">
<header class="topbar"><div class="top-inner">
  <a class="brand" href="{{ route('home') }}"><span class="brand-icon"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bear"></use></svg></span><span>СказкоМир<small>ЧИТАЕМ ВМЕСТЕ</small></span></a>
  <nav class="nav" id="nav" aria-label="Основная навигация"><a href="{{ route('home') }}">Главная</a><a href="{{ route('home') }}">Сказки</a><a href="{{ route('articles.index') }}">Родителям</a><a href="{{ route('prototype') }}">Все разделы</a>@guest<a href="{{ route('login') }}">Войти</a><a href="{{ route('register') }}">Регистрация</a>@endguest</nav>
  <div class="right-head">
  @auth
    @if(auth()->user()->hasAnyRole(['admin','moderator','editor']))<a class="icon-btn" href="{{ route('admin.dashboard') }}">Панель управления</a>@endif
    <a class="icon-btn" href="{{ route('profile.show') }}">{{ auth()->user()->name }}</a><form method="post" action="{{ route('logout') }}">@csrf<button class="icon-btn" type="submit">Выйти</button></form>
  @else
    <a class="icon-btn" href="{{ route('login') }}">Войти</a><a class="icon-btn" href="{{ route('register') }}">Создать аккаунт</a>
  @endauth
  </div><button class="menu-btn" id="menuBtn" type="button" aria-label="Открыть меню" aria-expanded="false" aria-controls="nav">☰</button>
</div>@unless(request()->routeIs('admin.*'))<div class="ticker" aria-label="Популярные сказки"><div class="ticker-in"><span class="ticker-label">★ Сегодня читают</span>@foreach(\App\Models\Story::query()->where('status','published')->orderByDesc('updated_at')->limit(6)->get() as $tickerStory)<a class="ticker-book" href="{{ route('stories.show',$tickerStory) }}">@if($tickerStory->cover_path)<img src="{{ asset($tickerStory->cover_path) }}" alt="" loading="lazy">@endif<span><b>{{ $tickerStory->title }}</b><small>{{ $tickerStory->author ?: 'СказкоМир' }}</small></span></a>@endforeach</div></div>@endunless</header>
<main class="page-wrap">
  @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
  @yield('content')
</main>
<footer class="footer"><div class="footer-in"><div><a class="brand" href="{{ route('home') }}"><span class="brand-icon"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bear"></use></svg></span><span>СказкоМир<small>ЧИТАЕМ ВМЕСТЕ</small></span></a><p class="footer-about">Добрые истории, аудиосказки и занятия для детей и родителей.</p></div><nav class="footer-links" aria-label="Ссылки в подвале"><a href="{{ route('home') }}">Сказки</a><a href="{{ route('articles.index') }}">Родителям</a><a href="{{ route('profile.show') }}">Личный кабинет</a><a href="{{ route('prototype') }}">Все разделы</a></nav></div><div class="copyright">© {{ date('Y') }} СказкоМир · Читаем вместе</div></footer>
<script src="{{ asset('js/app.js') }}?v=9" defer></script>
</body>
</html>
