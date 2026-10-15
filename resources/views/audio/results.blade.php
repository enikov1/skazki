<div class="audio-list" aria-busy="false">
  @forelse($stories as $story)
    @php
      $audioUrl = route('stories.audio', $story);
      $coverUrl = $story->cover_path ? asset($story->cover_path) : asset('assets/illustrations/audio-story.webp');
    @endphp
    <article class="audio-item audio-row has-audio" data-audio-id="{{ $story->slug }}" data-audio-title="{{ $story->title }}" data-audio-author="{{ $story->author ?: 'СказкоМир' }}" data-audio-cover="{{ $coverUrl }}" data-audio-src="{{ $audioUrl }}" @if($story->allow_download)data-audio-download-url="{{ route('stories.audio.download', $story) }}"@endif data-genre="{{ $story->genre }}" data-age-min="{{ $story->age_min }}" data-age-max="{{ $story->age_max }}">
      <a class="audio-row-cover" href="{{ route('stories.show', $story) }}" tabindex="-1" aria-hidden="true"><img src="{{ $coverUrl }}" alt="" loading="lazy"></a>
      <div class="audio-row-copy"><h3><a href="{{ route('stories.show', $story) }}">{{ $story->title }}</a></h3><p>{{ $story->author ?: 'СказкоМир' }} · {{ $story->genre }} · {{ $story->age_min }}–{{ $story->age_max }} лет</p><span class="audio-availability is-ready">Можно слушать</span></div>
      <div class="audio-row-actions"><button class="audio-play-button" type="button" data-audio-play="{{ $story->slug }}" aria-label="Слушать «{{ $story->title }}»"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#play"></use></svg><span>Слушать</span></button><button class="audio-queue-button" type="button" data-queue-add="{{ $story->slug }}" aria-label="Добавить «{{ $story->title }}» в очередь" title="Добавить в очередь"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#queue-add"></use></svg></button><a class="audio-details-link" href="{{ route('stories.show', $story) }}" aria-label="Подробнее о сказке «{{ $story->title }}»"><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#arrow-right"></use></svg></a></div>
    </article>
  @empty
    <div class="empty-state"><span aria-hidden="true">🎧</span><h2>Аудиосказок пока нет</h2><p>Загляните позже — новые записи появятся здесь.</p></div>
  @endforelse
</div>
<div class="audio-pagination">{{ $stories->links('vendor.pagination.skazki') }}</div>
