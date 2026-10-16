<div class="games-grid">
    @forelse($stories as $story)
        <article class="game-card">
            <a class="game-card-cover" href="{{ route('stories.quiz', $story) }}">@if($story->cover_path)<img src="{{ asset($story->cover_path) }}" alt="" loading="lazy">@else<span aria-hidden="true">🧩</span>@endif</a>
            <div class="game-card-copy"><p class="eyebrow">{{ $story->genre }} · {{ $story->age_min }}–{{ $story->age_max }} лет</p><h3><a href="{{ route('stories.quiz', $story) }}">Викторина по сказке «{{ $story->title }}»</a></h3><p>{{ $story->summary }}</p><small>{{ $story->quiz_questions_count }} {{ trans_choice('вопрос|вопроса|вопросов', $story->quiz_questions_count) }}</small></div>
            <a class="button" href="{{ route('stories.quiz', $story) }}">Играть</a>
        </article>
    @empty
        <div class="empty-state"><span aria-hidden="true">🧩</span><h2>Подходящих викторин пока нет</h2><p>Попробуйте изменить фильтры или загляните позже.</p></div>
    @endforelse
</div>
{{ $stories->links('vendor.pagination.skazki') }}
