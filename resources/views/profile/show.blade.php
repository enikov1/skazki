@extends('layouts.app')
@section('title', match($section) { 'history' => 'История — Мой профиль', 'settings' => 'Настройки — Мой профиль', 'security' => 'Безопасность — Мой профиль', default => 'Мой профиль — СказкоМир' })
@section('robots', 'noindex,nofollow')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span>@if($section !== 'overview')<a href="{{ route('profile.show') }}">Мой профиль</a><span>/</span>@endif<span aria-current="page">{{ ['overview' => 'Мой профиль', 'history' => 'История и очередь', 'settings' => 'Настройки', 'security' => 'Безопасность'][$section] }}</span></nav>

<section class="profile-welcome">
  @if($avatarUrl)
    <img class="profile-avatar profile-avatar-image" src="{{ $avatarUrl }}" alt="Фото профиля {{ $user->name }}" width="68" height="68">
  @else
    <div class="profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
  @endif
  <div class="profile-welcome-copy"><p class="eyebrow">Ваше пространство</p><h1>{{ $section === 'overview' ? 'Здравствуйте, '.$user->name.'!' : ['history' => 'Ваша история', 'settings' => 'Настройки профиля', 'security' => 'Безопасность аккаунта'][$section] }}</h1><p class="profile-subtitle">Настройки, безопасность и недавно открытые сказки — всё рядом.</p></div>
  @if($section === 'overview')<div class="profile-stat"><strong>{{ $user->readingHistories()->whereHas('story', fn ($query) => $query->published())->count() }}</strong><span>сказок в истории</span></div>@endif
</section>

<div class="profile-layout">
  <nav class="profile-nav" aria-label="Разделы профиля">
    <a href="{{ route('profile.show') }}" @class(['active' => $section === 'overview']) @if($section === 'overview') aria-current="page" @endif><span aria-hidden="true">⌂</span>Главная профиля</a>
    <a href="{{ route('profile.history') }}" @class(['active' => $section === 'history']) @if($section === 'history') aria-current="page" @endif><span aria-hidden="true">◷</span>История и очередь</a>
    <a href="{{ route('profile.settings') }}" @class(['active' => $section === 'settings']) @if($section === 'settings') aria-current="page" @endif><span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#settings"></use></svg></span>Настройки</a>
    <a href="{{ route('profile.security') }}" @class(['active' => $section === 'security']) @if($section === 'security') aria-current="page" @endif><span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#user"></use></svg></span>Безопасность</a>
  </nav>

  <div class="profile-content">
    @if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @include('profile.sections.'.$section)
  </div>
</div>
@endsection
