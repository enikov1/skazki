@extends('layouts.app')
@section('title', 'Подтвердите почту — СказкоМир')
@section('content')<section class="auth-panel"><p class="eyebrow">Ещё один шаг</p><h1>Проверьте почту</h1><p class="lead">Мы отправили письмо для подтверждения адреса. Перейдите по ссылке из письма.</p>@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif<form method="post" action="{{ route('verification.send') }}" class="stack-form">@csrf<button class="button button-wide" type="submit">Отправить письмо ещё раз</button></form></section>@endsection
