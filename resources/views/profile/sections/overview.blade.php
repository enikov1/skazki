<section class="profile-overview-grid" aria-label="Обзор профиля">
  <a class="profile-shortcut" href="{{ route('profile.history') }}"><span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#book"></use></svg></span><span><strong>История и очередь</strong><small>Продолжите чтение или прослушивание</small></span><b aria-hidden="true">→</b></a>
  <a class="profile-shortcut" href="{{ route('profile.settings') }}"><span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#palette"></use></svg></span><span><strong>Настройки</strong><small>Личные данные и удобство чтения</small></span><b aria-hidden="true">→</b></a>
  <a class="profile-shortcut" href="{{ route('profile.security') }}"><span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#lock"></use></svg></span><span><strong>Безопасность</strong><small>Пароль и связанные аккаунты</small></span><b aria-hidden="true">→</b></a>
</section>

<section class="profile-achievements" aria-labelledby="profileAchievementsTitle">
  <div class="section-heading"><div><p class="eyebrow">Ваш путь</p><h2 id="profileAchievementsTitle">Достижения</h2></div><span class="result-count">{{ collect($achievements)->filter(fn ($achievement) => $achievement['progress'] >= $achievement['target'])->count() }} из {{ count($achievements) }}</span></div>
  <div class="profile-achievement-grid">
    @foreach($achievements as $achievement)
      @php($progress = min($achievement['progress'], $achievement['target']))
      <a class="profile-achievement {{ $progress >= $achievement['target'] ? 'is-earned' : '' }}" href="{{ $achievement['url'] }}">
        <span class="profile-achievement-icon" aria-hidden="true">{{ $achievement['icon'] }}</span>
        <span class="profile-achievement-copy"><strong>{{ $achievement['title'] }}</strong><small>{{ $achievement['description'] }}</small></span>
        <progress max="{{ $achievement['target'] }}" value="{{ $progress }}" aria-label="{{ $achievement['title'] }}: {{ $progress }} из {{ $achievement['target'] }}"></progress>
        <span class="profile-achievement-count">{{ $progress }}/{{ $achievement['target'] }}</span>
      </a>
    @endforeach
  </div>
</section>

<section class="profile-history profile-overview-history"><div class="section-heading"><div><p class="eyebrow">Недавно открытые</p><h2>История чтения</h2></div><a class="text-link" href="{{ route('profile.history') }}">Вся история →</a></div>
  @php($recentHistory = $user->readingHistories()->with('story')->whereHas('story', fn ($query) => $query->published())->latest('last_read_at')->limit(3)->get())
  @if($recentHistory->isNotEmpty())<div class="history-list">@foreach($recentHistory as $entry)<a class="history-item" href="{{ route('stories.show', $entry->story) }}"><span class="history-cover">@if($entry->story->cover_path)<img src="{{ asset($entry->story->cover_path) }}" alt="" width="96" height="72" loading="lazy">@else<span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#book"></use></svg></span>@endif</span><span class="history-title"><strong>{{ $entry->story->title }}</strong><small>{{ $entry->story->author ?: $entry->story->genre }} · {{ $entry->story->reading_minutes }} мин</small></span><span class="history-arrow" aria-hidden="true">→</span></a>@endforeach</div>
  @else<div class="empty-state"><span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#book"></use></svg></span><h2>История пока пуста</h2><p>Откройте любую сказку, и она появится здесь.</p><a class="btn secondary" href="{{ route('home') }}">Выбрать сказку</a></div>@endif
</section>

<section class="profile-history profile-overview-history"><div class="section-heading"><div><p class="eyebrow">Ваше место</p><h2>Продолжить слушать</h2></div><a class="text-link" href="{{ route('profile.history') }}">Всё аудио →</a></div>
  @if($audioProgress->isNotEmpty())<div class="history-list">@foreach($audioProgress as $entry)<a class="history-item" href="{{ route('stories.show', $entry->story) }}#storyAudio"><span class="history-cover">@if($entry->story->cover_path)<img src="{{ asset($entry->story->cover_path) }}" alt="" width="96" height="72" loading="lazy">@else<span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#headphones"></use></svg></span>@endif</span><span class="history-title"><strong>{{ $entry->story->title }}</strong><small>{{ $entry->story->author ?: $entry->story->genre }} · {{ gmdate('i:s', $entry->position_seconds) }}</small></span><span class="history-arrow" aria-hidden="true">→</span></a>@endforeach</div>
  @else<div class="empty-state"><span aria-hidden="true"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#headphones"></use></svg></span><h2>Нет незавершённых аудиосказок</h2><p>Начните слушать — прогресс сохранится автоматически.</p><a class="btn secondary" href="{{ route('audio.index') }}">Выбрать аудиосказку</a></div>@endif
</section>
