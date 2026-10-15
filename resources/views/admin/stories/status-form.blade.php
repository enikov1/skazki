<form class="status-form" method="post" action="{{ route('admin.stories.status', $story) }}" data-ajax-form>
    @csrf
    @method('patch')
    <label class="field">
        <span>Статус</span>
        <select name="status">
            @foreach(\App\Enums\EditorialStatus::cases() as $status)
                <option value="{{ $status->value }}" {{ $story->status === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
            @endforeach
        </select>
    </label>
    <label class="field">
        <span>Опубликовать в указанное время</span>
        <input type="datetime-local" name="publish_at" value="{{ $story->published_at?->isFuture() ? $story->published_at->format('Y-m-d\\TH:i') : '' }}">
        <small class="field-help">Оставьте пустым для немедленной публикации. Будущая дата скроет сказку до этого времени.</small>
    </label>
    <button class="button button-soft" type="submit">Обновить статус</button>
</form>
