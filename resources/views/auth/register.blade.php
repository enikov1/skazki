@extends('layouts.app')
@section('title', 'Регистрация — СказкоМир')
@section('content')
<form class="searchbar" id="searchForm" role="search" action="{{ route('home') }}" method="get"><label class="visually-hidden" for="globalSearch">Поиск по библиотеке</label><span aria-hidden="true"><svg class="icon"><use href="{{ asset('assets/icons.svg') }}#search"></use></svg></span><input id="globalSearch" type="search" name="q" placeholder="Найти сказку для чтения или прослушивания…"><button type="submit" aria-label="Найти"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#search"></use></svg></button></form>
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><span aria-current="page">Регистрация</span></nav>
<div class="auth-layout">
  @include('auth.partials.aside', ['label' => 'Добро пожаловать', 'heading' => 'Ваша история начинается здесь', 'description' => 'Создайте аккаунт, чтобы возвращаться к любимым сказкам и продолжать чтение.', 'art' => 'assets/illustrations/auth-register.svg', 'alt' => 'Медвежонок читает книжку'])
  <section class="auth-card" aria-labelledby="authTitle"><span class="auth-kicker">Новый читатель</span><h2 id="authTitle">Создать аккаунт</h2><p class="auth-description">Заполните несколько полей, чтобы присоединиться к СказкоМиру.</p>
    @if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="social-login" aria-label="Создать аккаунт через социальную сеть"><a class="social-button social-yandex" href="{{ route('oauth.redirect', 'yandex') }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#yandex"></use></svg> Продолжить с Яндексом</a><a class="social-button social-vk" href="{{ route('oauth.redirect', 'vkontakte') }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#vk"></use></svg> Продолжить с VK</a></div>
    <div class="auth-divider"><span>или по электронной почте</span></div>
    <form class="auth-form" method="post" action="{{ route('register.store') }}" data-ajax-form>@csrf
      <div class="field"><label for="registerName">Как к вам обращаться</label><input id="registerName" type="text" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" required></div>
      <div class="field"><label for="registerEmail">Электронная почта</label><input id="registerEmail" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required></div>
      <div class="field"><label for="registerPassword">Пароль — не короче 10 символов</label><input id="registerPassword" type="password" name="password" autocomplete="new-password" minlength="10" required></div>
      <div class="field"><label for="registerPasswordConfirm">Повторите пароль</label><input id="registerPasswordConfirm" type="password" name="password_confirmation" autocomplete="new-password" minlength="10" required></div>
      <label class="check-line"><input type="checkbox" name="terms" value="1" @checked(old('terms')) required><span>Согласен(на) с правилами сайта и политикой конфиденциальности</span></label><label class="check-line"><input type="checkbox" name="newsletter" value="1" @checked(old('newsletter'))><span>Хочу получать новости проекта по почте <small>(по желанию, выбор можно изменить в профиле)</small></span></label>
      <button class="btn auth-submit" type="submit">Зарегистрироваться</button>
    </form>
    <p class="auth-switch">Уже есть аккаунт? <a href="{{ route('login') }}">Войти</a></p><p class="auth-legal">Продолжая, вы соглашаетесь с <a href="{{ route('privacy-policy') }}">политикой конфиденциальности</a>.</p>
  </section>
</div>
@endsection
