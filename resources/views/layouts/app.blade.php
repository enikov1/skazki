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
  <link rel="stylesheet" href="{{ asset('css/site.css') }}">
  @stack('head')
</head>
<body>
<header class="site-header"><div class="wrap header-row">
  <a class="brand" href="{{ route('home') }}"><span class="brand-mark" aria-hidden="true">✿</span><span>СказкоМир<small>ЧИТАЕМ ВМЕСТЕ</small></span></a>
  <nav class="primary-nav" aria-label="Главное меню"><a href="{{ route('home') }}">Сказки</a><a href="{{ route('articles.index') }}">Родителям</a><a href="{{ route('home', ['genre' => 'Народные сказки']) }}">Народные сказки</a><a href="{{ route('prototype') }}">Все разделы</a></nav>
  <div class="header-actions">
  @auth
    @if(auth()->user()->hasAnyRole(['admin','moderator','editor']))<a class="button button-small button-soft" href="{{ route('admin.dashboard') }}">Панель управления</a>@endif
    <span class="user-greeting">{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="button button-small button-quiet" type="submit">Выйти</button></form>
  @else
    <a class="button button-small button-soft" href="{{ route('login') }}">Войти</a><a class="button button-small" href="{{ route('register') }}">Создать аккаунт</a>
  @endauth
  </div>
</div></header>
<main class="wrap main-content">
  @if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
  @yield('content')
</main>
<footer class="site-footer"><div class="wrap footer-row"><a class="brand" href="{{ route('home') }}"><span class="brand-mark" aria-hidden="true">✿</span><span>СказкоМир</span></a><span>Добрые истории для семейного чтения</span><a href="{{ route('prototype') }}">Все страницы проекта</a></div></footer>
</body>
</html>
