@extends('layouts.app')
@section('title', 'Викторина по сказке «'.$story->title.'» — СказкоМир')
@if(request()->filled('result'))@section('robots', 'noindex,nofollow')@endif
@section('content')
<nav class="breadcrumbs" aria-label="Хлебные крошки"><a href="{{ route('home') }}">Главная</a><span>/</span><a href="{{ route('stories.show', $story) }}">{{ $story->title }}</a><span>/</span><span aria-current="page">Викторина</span></nav>
<section class="page-panel quiz-page"><p class="eyebrow">Викторина по сказке</p><h1>Что вы запомнили?</h1><p class="lead">Ответьте на вопросы о сказке «{{ $story->title }}».</p>
@if($result)<section class="quiz-result" role="status" data-print-area="quiz-result"><span class="quiz-result-icon" aria-hidden="true"><svg class="icon"><use href="{{ asset('assets/icons.svg') }}#{{ $result->score === $result->question_count ? 'medal' : 'book' }}"></use></svg></span><div data-print-hide><button class="button button-quiet" type="button" data-print-page><svg class="icon" aria-hidden="true"><use href="{{ asset('assets/icons.svg') }}#printer"></use></svg> Распечатать результат</button></div><h2>Ваш результат: {{ $result->score }} из {{ $result->question_count }}</h2><p>{{ $result->score === $result->question_count ? 'Прекрасно! Вы отлично знаете эту историю.' : 'Хорошая работа! Перечитайте сказку и попробуйте ещё раз.' }}</p><a class="button button-soft" href="{{ route('stories.read', $story) }}">Перечитать сказку</a> <a class="button" href="{{ route('stories.quiz', $story) }}">Пройти ещё раз</a></section>
@else
<form method="post" action="{{ route('stories.quiz.submit', $story) }}" data-ajax-form class="quiz-question-list">@csrf
@foreach($questions as $index => $question)<fieldset class="quiz-question"><legend><span class="quiz-question-number">{{ $index + 1 }}</span>{{ $question->prompt }}</legend><div class="quiz-answer-list">@foreach($question->options as $optionIndex => $option)<label class="quiz-answer"><input type="radio" name="answers[{{ $index }}]" value="{{ $optionIndex }}" required><span>{{ $option }}</span></label>@endforeach</div></fieldset>@endforeach
@error('answers')<p class="field-error" role="alert">{{ $message }}</p>@enderror<button class="button" type="submit">Проверить ответы</button>
</form>@endif
<a class="text-link" href="{{ route('stories.show', $story) }}">← Вернуться к сказке</a></section>
@endsection
