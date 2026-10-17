@extends('layouts.app')
@section('title', 'Викторина: '.$story->title.' — Панель управления')
@section('content')
<nav class="breadcrumbs"><a href="{{ route('admin.dashboard') }}">Панель управления</a><span>/</span><a href="{{ route('admin.stories.edit', $story) }}">{{ $story->title }}</a><span>/</span><span>Викторина</span></nav>
<div class="admin-topline"><div><p class="eyebrow">Редактор библиотеки</p><h1>Викторина по сказке</h1><p class="lead">{{ $story->title }} · добавьте до 20 вопросов, по четыре варианта ответа.</p></div></div>
<form method="post" action="{{ route('admin.stories.quiz.update', $story) }}" data-ajax-form class="quiz-admin-form">@csrf @method('put')
<div class="quiz-admin-questions" data-quiz-questions>@foreach($questions as $index => $question)<fieldset class="quiz-admin-question"><legend>Вопрос <span data-question-number>{{ $index + 1 }}</span><button type="button" class="button button-quiet button-small" data-remove-question>Удалить</button></legend><label class="field"><span>Текст вопроса</span><input name="questions[{{ $index }}][prompt]" value="{{ $question->prompt }}" maxlength="500" required></label><div class="quiz-admin-options">@foreach($question->options as $optionIndex => $option)<label class="field"><span>Вариант {{ $optionIndex + 1 }}</span><input name="questions[{{ $index }}][options][{{ $optionIndex }}]" value="{{ $option }}" maxlength="180" required></label>@endforeach</div><label class="field"><span>Правильный ответ</span><select name="questions[{{ $index }}][correct_option]">@foreach($question->options as $optionIndex => $option)<option value="{{ $optionIndex }}" @selected($question->correct_option === $optionIndex)>Вариант {{ $optionIndex + 1 }}</option>@endforeach</select></label></fieldset>@endforeach</div>
<template data-quiz-question-template><fieldset class="quiz-admin-question"><legend>Вопрос <span data-question-number></span><button type="button" class="button button-quiet button-small" data-remove-question>Удалить</button></legend><label class="field"><span>Текст вопроса</span><input data-name="prompt" maxlength="500" required></label><div class="quiz-admin-options">@for($option=0;$option<4;$option++)<label class="field"><span>Вариант {{ $option+1 }}</span><input data-option="{{ $option }}" maxlength="180" required></label>@endfor</div><label class="field"><span>Правильный ответ</span><select data-name="correct_option"><option value="0">Вариант 1</option><option value="1">Вариант 2</option><option value="2">Вариант 3</option><option value="3">Вариант 4</option></select></label></fieldset></template>
<div class="tool-row"><button type="button" class="button button-soft" data-add-quiz-question>Добавить вопрос</button><button type="submit" class="button">Сохранить викторину</button><a class="button button-quiet" href="{{ route('admin.stories.edit', $story) }}">К сказке</a></div><p class="form-hint">Если удалить все вопросы, викторина исчезнет со страницы сказки. Результаты читателей сохраняются отдельно.</p>
</form>
<script>
document.addEventListener('click', function (event) {
  const container = document.querySelector('[data-quiz-questions]');
  if (!container) return;
  if (event.target.closest('[data-add-quiz-question]')) {
    if (container.children.length >= 20) return;
    const indexes = [...container.querySelectorAll('[name^="questions["]')].map((field) => Number(field.name.match(/^questions\[(\d+)\]/)?.[1] ?? -1));
    const index = Math.max(-1, ...indexes) + 1;
    const node = document.querySelector('[data-quiz-question-template]').content.cloneNode(true);
    node.querySelectorAll('[data-name]').forEach((field) => field.name = `questions[${index}][${field.dataset.name}]`);
    node.querySelectorAll('[data-option]').forEach((field) => field.name = `questions[${index}][options][${field.dataset.option}]`);
    node.querySelector('[data-question-number]').textContent = index + 1;
    container.append(node);
  }
  if (event.target.closest('[data-remove-question]')) {
    event.target.closest('.quiz-admin-question').remove();
    container.querySelectorAll('[data-question-number]').forEach((label, index) => label.textContent = index + 1);
  }
});
</script>
@endsection
