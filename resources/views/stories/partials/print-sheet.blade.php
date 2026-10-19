<section class="story-print-sheet" aria-label="Версия сказки для печати">
  <h1>{{ $story->title }}</h1>
  @if($story->cover_path)<img class="story-print-illustration" src="{{ asset($story->cover_path) }}" alt="Иллюстрация к сказке «{{ $story->title }}">@endif
  <article class="story-print-text">{!! app(\App\Support\BBEditor::class)->render($story->body ?: $story->summary) !!}</article>
  @if(!empty($printLogoUrl))<img class="story-print-watermark" src="{{ $printLogoUrl }}" alt="Логотип СказкоМир">@endif
</section>
