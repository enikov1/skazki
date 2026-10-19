<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex,nofollow">
  <meta name="theme-color" content="#fffaf1">
  <title>@yield('title', 'Что-то пошло не так — СказкоМир')</title>
  <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=11">
  <link rel="stylesheet" href="{{ asset('css/ui.css') }}?v=2">
  <style>
    .error-page{min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(ellipse at 15% 10%,#e7f6fc 0,transparent 42%),radial-gradient(ellipse at 90% 90%,#fff0df 0,transparent 38%),#fffaf4}
    .error-card{width:min(620px,100%);padding:clamp(24px,6vw,52px);border:1px solid #e5edf0;border-radius:24px;background:#ffffffed;box-shadow:0 20px 60px #29485812;text-align:center}
    .error-brand{display:inline-flex;align-items:center;gap:10px;color:#263e4c;font-weight:800;font-size:18px}.error-brand .icon{width:26px;height:26px;color:#258bb5}
    .error-code{margin:32px 0 2px;color:#258bb5;font-size:clamp(56px,14vw,100px);font-weight:800;line-height:1;letter-spacing:-.06em}
    .error-card h1{margin:12px 0;font-size:clamp(23px,5vw,32px);line-height:1.2}.error-card p{max-width:450px;margin:0 auto;color:#637780}
    .error-actions{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:26px}.error-actions .button{min-width:145px}
    @media(max-width:430px){.error-card{border-radius:18px}.error-actions{display:grid}.error-actions .button{width:100%}}
  </style>
</head>
<body>
<main class="error-page">
  <section class="error-card" aria-labelledby="error-title">
    <a class="error-brand" href="{{ route('home') }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bear"></use></svg>СказкоМир</a>
    <div class="error-code" aria-hidden="true">@yield('code', '500')</div>
    <h1 id="error-title">@yield('heading', 'Что-то пошло не так')</h1>
    <p>@yield('message', 'Страница сейчас недоступна. Попробуйте вернуться или открыть главную страницу.') </p>
    <div class="error-actions">
      <a class="button button-quiet" href="{{ route('home') }}" data-history-back><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-left"></use></svg> Назад</a>
      <a class="button" href="{{ route('home') }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#house"></use></svg> На главную</a>
    </div>
  </section>
</main>
<script>
  document.querySelector('[data-history-back]')?.addEventListener('click', function (event) {
    if (document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) {
      event.preventDefault(); history.back();
    }
  });
</script>
</body>
</html>
