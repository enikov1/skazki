@extends('layouts.app')
@section('title', 'Вход — СказкоМир')
@section('content')
<form class="searchbar" id="searchForm" role="search" action="{{ route('home') }}" method="get"><label class="visually-hidden" for="globalSearch">Поиск по библиотеке</label><span aria-hidden="true"><svg class="icon"><use href="{{ asset('assets/icons.svg') }}#search"></use></svg></span><input id="globalSearch" type="search" name="q" placeholder="Найти сказку для чтения или прослушивания…"><button type="submit" aria-label="Найти"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#search"></use></svg></button></form>
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><span aria-current="page">Вход</span></nav>
<div class="auth-layout">
  @include('auth.partials.aside', ['label' => 'Личный кабинет', 'heading' => 'Снова рады вас видеть', 'description' => 'Войдите, чтобы читать любимые истории и продолжить с того места, где остановились.', 'art' => 'assets/illustrations/auth-login.svg', 'alt' => 'Медвежонок и зайчонок читают вместе'])
  <section class="auth-card" aria-labelledby="authTitle"><span class="auth-kicker">Вход в аккаунт</span><h2 id="authTitle">Войти в СказкоМир</h2><p class="auth-description">Введите адрес электронной почты и пароль или выберите удобный способ входа.</p>
    @if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="social-login" aria-label="Войти через социальную сеть"><a class="social-button social-yandex" href="{{ route('oauth.redirect', 'yandex') }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#yandex"></use></svg> Продолжить с Яндексом</a><a class="social-button social-vk" href="{{ route('oauth.redirect', 'vkontakte') }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#vk"></use></svg> Продолжить с VK</a></div>
    <div class="auth-divider"><span>или по электронной почте</span></div>
    <form class="auth-form" method="post" action="{{ route('login.store') }}" data-ajax-form>@csrf
      <div class="field"><label for="loginEmail">Электронная почта</label><input id="loginEmail" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="ваша почта" required autofocus><small class="field-help">Вход выполняется по адресу почты, а не по имени пользователя.</small></div>
      <div class="field"><div class="field-heading"><label for="loginPassword">Пароль</label><a href="{{ route('password.request') }}">Забыли пароль?</a></div><input id="loginPassword" type="password" name="password" autocomplete="current-password" placeholder="Пароль" required></div>
      <label class="check-line"><input type="checkbox" name="remember" value="1"><span>Запомнить меня на этом устройстве</span></label>
      <button class="btn auth-submit" type="submit">Войти</button>
    </form>
    <p class="auth-switch">Нет аккаунта? <a href="{{ route('register') }}">Зарегистрироваться</a></p><p class="auth-legal">Продолжая, вы соглашаетесь с <a href="{{ route('privacy-policy') }}">политикой конфиденциальности</a>.</p>
  </section>
</div>
@endsection
