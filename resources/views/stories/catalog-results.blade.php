<p class="story-catalog-count">Найдено сказок: {{ $stories->total() }}</p>
<div class="book-row story-catalog-grid">
  @forelse($stories as $index => $story)
    <a class="book-card" href="{{ route('stories.show', $story) }}"><div class="cover {{ ['c1','c2','c3','c4','c5','c6','c7','c8','c9','c10','c11','c12'][$index % 12] }}">@if($story->cover_path)<img class="cover-img" src="{{ asset($story->cover_path) }}" alt="Иллюстрация к сказке «{{ $story->title }}»" loading="lazy">@endif<span class="mini-title">{{ $story->title }}</span></div><h2>{{ $story->title }}</h2><div class="author">{{ $story->author ?: 'СказкоМир' }}</div><p>{{ $story->summary }}</p><small>{{ $story->genre }} · {{ $story->age_min }}–{{ $story->age_max }} лет</small></a>
  @empty<p class="empty">По вашему запросу сказок не найдено. Попробуйте изменить фильтры.</p>@endforelse
</div>
{{ $stories->links('vendor.pagination.skazki') }}
