@php
  $providers = collect(['yandex' => 'Яндексом', 'vkontakte' => 'VK'])->filter(fn ($label, $provider) => filled(config('services.'.$provider.'.client_id')) && filled(config('services.'.$provider.'.client_secret')));
@endphp
@if($providers->isNotEmpty())
  <div class="social-login" aria-label="Войти через социальную сеть">
    @foreach($providers as $provider => $label)
      <a class="social-button social-{{ $provider === 'yandex' ? 'yandex' : 'vk' }}" href="{{ route('oauth.redirect', $provider) }}"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#{{ $provider === 'yandex' ? 'yandex' : 'vk' }}"></use></svg>Продолжить с {{ $label }}</a>
    @endforeach
  </div>
  <div class="auth-divider"><span>или по электронной почте</span></div>
@endif
