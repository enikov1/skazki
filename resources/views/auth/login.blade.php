@extends('layouts.app')
@section('title', 'Вход в аккаунт — СказкоМир')
@section('content')<section class="auth-panel"><p class="eyebrow">С возвращением</p><h1>Войти в СказкоМир</h1><p class="lead">Продолжайте читать и сохраняйте любимые истории.</p>
 @if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
 <form method="post" action="{{ route('login.store') }}" class="stack-form">@csrf<label class="field"><span>Электронная почта</span><input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></label><label class="field"><span>Пароль</span><input type="password" name="password" autocomplete="current-password" required></label><label class="check-line"><input type="checkbox" name="remember" value="1"><span>Не выходить из аккаунта</span></label><button class="button button-wide" type="submit">Войти</button><a class="text-link" href="{{ route('password.request') }}">Забыли пароль?</a></form>
 <div class="oauth-row"><a class="button button-oauth" href="{{ route('oauth.redirect', 'yandex') }}">Войти через Яндекс</a><a class="button button-oauth" href="{{ route('oauth.redirect', 'vkontakte') }}">Войти через VK</a></div><p class="form-foot">Ещё нет аккаунта? <a href="{{ route('register') }}">Зарегистрироваться</a></p>
</section>@endsection
