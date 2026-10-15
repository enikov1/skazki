<aside class="auth-aside">
  <a class="brand" href="{{ route('home') }}"><span class="brand-icon"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#bear"></use></svg></span><span>СказкоМир<small>ЧИТАЕМ ВМЕСТЕ</small></span></a>
  <span class="auth-kicker">{{ $label }}</span><h1>{{ $heading }}</h1><p>{{ $description }}</p>
  @if(!empty($art))<img class="auth-illustration" src="{{ asset($art) }}" alt="{{ $alt }}">@endif
  <div class="auth-aside-note"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#book"></use></svg><span>Уютная библиотека для всей семьи</span></div>
</aside>
