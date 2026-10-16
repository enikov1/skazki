<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="Чтение сказки {{ $story->title }} в читалке СказкоМира.">
  <meta name="robots" content="@yield('robots', 'noindex,nofollow')">
  <meta name="theme-color" content="#f5f1e8">
  <title>@yield('title', 'Читалка — СказкоМир')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=13">
  <link rel="stylesheet" href="{{ asset('css/ui.css') }}?v=5">
  <link rel="stylesheet" href="{{ asset('css/reader.css') }}?v=2">
</head>
<body class="reader-mode" data-site-page="laravel">
  <main class="reader-page">@yield('content')</main>
  <div class="toast" id="toast" role="status" aria-live="polite"></div>
  <script src="{{ asset('js/app.js') }}?v=14" defer></script>
</body>
</html>
