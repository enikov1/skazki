@extends('layouts.app')
@section('title', 'Мой профиль — СказкоМир')
@section('robots', 'noindex,nofollow')
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><span aria-current="page">Мой профиль</span></nav>

<section class="profile-welcome">
  @if($avatarUrl)
    <img class="profile-avatar profile-avatar-image" src="{{ $avatarUrl }}" alt="Фото профиля {{ $user->name }}" width="68" height="68">
  @else
    <div class="profile-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
  @endif
  <div><p class="eyebrow">Ваше пространство</p><h1>Здравствуйте, {{ $user->name }}!</h1><p class="profile-subtitle">Настройки, безопасность и недавно открытые сказки — всё рядом.</p></div>
  <div class="profile-stat"><strong>{{ $history->total() }}</strong><span>сказок в истории</span></div>
</section>

@if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

<div class="profile-grid">
  <section class="profile-panel">
    <div class="profile-panel-heading"><span class="profile-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.5-3.5 3.1-5.5 7-5.5s6.5 2 7 5.5"/></svg></span><div><p class="eyebrow">Личные данные</p><h2>Информация о вас</h2></div></div>
    <div class="profile-email"><span>Электронная почта</span><strong>{{ $user->email }}</strong><span class="email-status {{ $user->hasVerifiedEmail() ? 'verified' : '' }}">{{ $user->hasVerifiedEmail() ? 'Адрес подтверждён' : 'Нужно подтвердить адрес' }}</span></div>
    <form class="stack-form" method="post" action="{{ route('profile.update') }}" data-ajax-form>@csrf @method('put')<label class="field"><span>Как к вам обращаться</span><input name="name" value="{{ old('name', $user->name) }}" minlength="2" maxlength="80" required autocomplete="name"></label><button class="button" type="submit">Сохранить имя</button></form>
    @unless($user->hasVerifiedEmail())<a class="text-link profile-verify-link" href="{{ route('verification.notice') }}">Подтвердить почту →</a>@endunless
    <div class="profile-divider"></div>
    <h3 class="profile-subheading">Фото профиля</h3><p class="profile-note">Загрузите портрет или любимую картинку до 2 МБ.</p>
    <form class="avatar-form" method="post" action="{{ route('profile.avatar.update') }}" data-ajax-form enctype="multipart/form-data">@csrf @method('put')<label class="field"><span>Выберите изображение</span><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></label><button class="button button-soft" type="submit">Обновить фото</button></form>
    @if($avatarUrl)<form class="avatar-remove-form" method="post" action="{{ route('profile.avatar.destroy') }}" data-ajax-form>@csrf @method('delete')<button class="text-link text-link-danger" type="submit">Удалить фото</button></form>@endif
  </section>

  <section class="profile-panel">
    <div class="profile-panel-heading"><span class="profile-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/></svg></span><div><p class="eyebrow">Безопасность</p><h2>{{ $user->password_login_enabled ? 'Изменить пароль' : 'Создать пароль' }}</h2></div></div>
    @unless($user->password_login_enabled)<p class="profile-note">Сейчас вы входите через привязанную сеть. Придумайте пароль, чтобы добавить вход по email.</p>@endunless
    <form class="stack-form" method="post" action="{{ route('profile.password') }}" data-ajax-form>@csrf @method('put')@if($user->password_login_enabled)<label class="field"><span>Текущий пароль</span><input type="password" name="current_password" required autocomplete="current-password"></label>@endif<label class="field"><span>Новый пароль</span><input type="password" name="password" minlength="12" required autocomplete="new-password"><small class="field-help">Не менее 12 символов, с заглавными и строчными буквами и цифрами.</small></label><label class="field"><span>Повторите новый пароль</span><input type="password" name="password_confirmation" minlength="12" required autocomplete="new-password"></label><button class="button button-soft" type="submit">{{ $user->password_login_enabled ? 'Обновить пароль' : 'Создать пароль' }}</button></form>
  </section>

  <section class="profile-panel">
    <div class="profile-panel-heading"><span class="profile-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 6h16M7 12h10m-7 6h4"/><circle cx="8" cy="6" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="11" cy="18" r="1.5"/></svg></span><div><p class="eyebrow">Для чтения</p><h2>Внешний вид сказок</h2></div></div>
    <p class="profile-note">Выберите удобные буквы и фон. Настройки применятся к тексту сказок.</p>
    <form class="stack-form" method="post" action="{{ route('profile.preferences') }}" data-ajax-form>@csrf @method('put')
      <label class="field"><span>Размер букв</span><select name="reader_font_size"><option value="16" @selected((int) $preferences['reader_font_size'] === 16)>Небольшой</option><option value="18" @selected((int) $preferences['reader_font_size'] === 18)>Чуть больше</option><option value="20" @selected((int) $preferences['reader_font_size'] === 20)>Обычный</option><option value="22" @selected((int) $preferences['reader_font_size'] === 22)>Крупный</option><option value="24" @selected((int) $preferences['reader_font_size'] === 24)>Очень крупный</option></select></label>
      <label class="field"><span>Цвет страницы</span><select name="reader_theme"><option value="cream" @selected($preferences['reader_theme'] === 'cream')>Тёплый</option><option value="white" @selected($preferences['reader_theme'] === 'white')>Белый</option><option value="green" @selected($preferences['reader_theme'] === 'green')>Мягкий зелёный</option></select></label>
      <label class="field"><span>Ширина текста</span><select name="reader_width"><option value="narrow" @selected($preferences['reader_width'] === 'narrow')>Узкая</option><option value="medium" @selected($preferences['reader_width'] === 'medium')>Средняя</option><option value="wide" @selected($preferences['reader_width'] === 'wide')>Широкая</option></select></label>
      <button class="button" type="submit">Сохранить настройки</button>
    </form>
  </section>

  <section class="profile-panel">
    <div class="profile-panel-heading"><span class="profile-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 21s-8-4.4-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.6-8 11-8 11Z"/></svg></span><div><p class="eyebrow">Ваш выбор</p><h2>Согласия</h2></div></div>
    <div class="consent-status-list"><div class="consent-status-row"><span>Правила сайта</span><strong>{{ isset($consentEvents['terms']) ? 'Принято' : 'Не указано' }}</strong>@if(isset($consentEvents['terms']))<time datetime="{{ $consentEvents['terms']->recorded_at->toAtomString() }}">{{ $consentEvents['terms']->recorded_at->translatedFormat('j F Y') }}</time>@endif</div><div class="consent-status-row"><span>Политика конфиденциальности</span><strong>{{ isset($consentEvents['privacy_notice']) ? 'Принято' : 'Не указано' }}</strong>@if(isset($consentEvents['privacy_notice']))<time datetime="{{ $consentEvents['privacy_notice']->recorded_at->toAtomString() }}">{{ $consentEvents['privacy_notice']->recorded_at->translatedFormat('j F Y') }}</time>@endif</div></div>
    @php($newsletterEnabled = $newsletterConsent?->action === 'granted')
    <form class="newsletter-consent" method="post" action="{{ route('profile.newsletter') }}" data-ajax-form>@csrf @method('put')<input type="hidden" name="newsletter" value="0"><label class="check-line"><input type="checkbox" name="newsletter" value="1" @checked($newsletterEnabled)><span><strong>Новости проекта по почте</strong><small>Можно изменить выбор в любой момент.</small></span></label><button class="button button-soft" type="submit">Сохранить выбор</button></form>
  </section>

  <section class="profile-panel profile-social-panel">
    <div class="profile-panel-heading"><span class="profile-panel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="7" cy="12" r="3"/><circle cx="17" cy="7" r="3"/><circle cx="17" cy="17" r="3"/><path d="m9.7 10.6 4.5-2.2m-4.5 5 4.5 2.2"/></svg></span><div><p class="eyebrow">Способы входа</p><h2>Связанные аккаунты</h2></div></div>
    <p class="profile-note">Привяжите сеть из своего профиля. Совпавшая почта сама по себе аккаунты не объединяет.</p>
    <div class="social-account-list">@foreach(['yandex' => 'Яндекс', 'vkontakte' => 'VK'] as $provider => $providerName)@php($linkedAccount = $socialAccounts->firstWhere('provider', $provider))<div class="social-account-row"><span><strong>{{ $providerName }}</strong><small>{{ $linkedAccount ? 'Аккаунт подключён' : 'Не подключён' }}</small></span>@if($linkedAccount)<form method="post" action="{{ route('profile.social-accounts.destroy', $linkedAccount) }}" data-ajax-form>@csrf @method('delete')<button class="text-link text-link-danger" type="submit">Отвязать</button></form>@else<a class="button button-small button-soft" href="{{ route('oauth.link', $provider) }}">Привязать</a>@endif</div>@endforeach</div>
    @if(!$user->password_login_enabled && $socialAccounts->count() === 1)<p class="field-help social-login-hint">Это последний способ входа. Сначала создайте пароль или привяжите другую сеть.</p>@endif
  </section>
</div>

<section class="profile-history"><div class="section-heading"><div><p class="eyebrow">Сохранённое место</p><h2>Продолжить слушать</h2></div><a class="text-link" href="{{ route('audio.index') }}">Все аудиосказки →</a></div>@if($audioProgress->isNotEmpty())<div class="history-list">@foreach($audioProgress as $entry)<a class="history-item" href="{{ route('stories.show', $entry->story) }}#storyAudio"><span class="history-cover">@if($entry->story->cover_path)<img src="{{ asset($entry->story->cover_path) }}" alt="" width="96" height="72" loading="lazy">@else<span aria-hidden="true">🎧</span>@endif</span><span class="history-title"><strong>{{ $entry->story->title }}</strong><small>{{ $entry->story->author ?: $entry->story->genre }} · {{ gmdate('i:s', $entry->position_seconds) }}</small></span><time datetime="{{ $entry->updated_at->toIso8601String() }}">Слушали {{ $entry->updated_at->translatedFormat('j F Y') }}</time><span class="history-arrow" aria-hidden="true">→</span></a>@endforeach</div>@else<div class="empty-state"><span aria-hidden="true">🎧</span><h2>Нет незавершённых аудиосказок</h2><p>Начните слушать — прогресс сохранится автоматически.</p><a class="button button-soft" href="{{ route('audio.index') }}">Выбрать аудиосказку</a></div>@endif</section>

<section class="profile-history"><div class="section-heading"><div><p class="eyebrow">Сохранено в аккаунте</p><h2>Очередь аудиосказок</h2></div><a class="text-link" href="{{ route('audio.index') }}">Открыть плеер →</a></div>@if($savedAudioQueue->isNotEmpty())<div class="history-list">@foreach($savedAudioQueue as $entry)<a class="history-item" href="{{ route('stories.show', $entry->story) }}#storyAudio"><span class="history-cover">@if($entry->story->cover_path)<img src="{{ asset($entry->story->cover_path) }}" alt="" width="96" height="72" loading="lazy">@else<span aria-hidden="true">🎧</span>@endif</span><span class="history-title"><strong>{{ $entry->story->title }}</strong><small>{{ $entry->story->author ?: $entry->story->genre }}</small></span><span class="result-count">№ {{ $entry->queue_position + 1 }}</span><span class="history-arrow" aria-hidden="true">→</span></a>@endforeach</div>@else<div class="empty-state"><span aria-hidden="true">🎶</span><h2>Очередь пока пуста</h2><p>Добавляйте сказки в плеере — очередь сохранится в профиле.</p><a class="button button-soft" href="{{ route('audio.index') }}">Выбрать аудиосказки</a></div>@endif</section>

<section class="profile-history"><div class="section-heading"><div><p class="eyebrow">Недавно читали</p><h2>История сказок</h2></div><span class="result-count">{{ $history->total() }} {{ trans_choice('история|истории|историй', $history->total()) }}</span></div>@if($history->count())<div class="history-list">@foreach($history as $entry)<a class="history-item" href="{{ route('stories.show', $entry->story) }}"><span class="history-cover">@if($entry->story->cover_path)<img src="{{ asset($entry->story->cover_path) }}" alt="" width="96" height="72" loading="lazy">@else<span aria-hidden="true">📖</span>@endif</span><span class="history-title"><strong>{{ $entry->story->title }}</strong><small>{{ $entry->story->author ?: $entry->story->genre }} · {{ $entry->story->reading_minutes }} мин</small></span><time datetime="{{ $entry->last_read_at->toIso8601String() }}">Открывали {{ $entry->last_read_at->translatedFormat('j F Y') }}</time><span class="history-arrow" aria-hidden="true">→</span></a>@endforeach</div>{{ $history->links('vendor.pagination.skazki') }}@else<div class="empty-state"><span aria-hidden="true">📚</span><h2>История пока пуста</h2><p>Откройте любую сказку, и она появится здесь.</p><a class="button button-soft" href="{{ route('home') }}">Выбрать сказку</a></div>@endif</section>
@endsection
