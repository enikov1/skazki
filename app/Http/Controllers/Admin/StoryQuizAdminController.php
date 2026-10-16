<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StoryQuizAdminController extends Controller
{
    use RespondsToAjax;

    public function edit(Story $story): View
    {
        Gate::authorize('update', $story);
        $questions = $story->quizQuestions()->orderBy('position')->get();
        return view('admin.stories.quiz', compact('story', 'questions'));
    }

    public function update(Request $request, Story $story): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $story);
        $input = collect($request->input('questions', []))
            ->filter(fn ($question) => filled($question['prompt'] ?? null))
            ->values()->all();
        $data = validator(['questions' => $input], [
            'questions' => ['present', 'array', 'max:20'],
            'questions.*.prompt' => ['required', 'string', 'min:5', 'max:500'],
            'questions.*.options' => ['required', 'array', 'size:4'],
            'questions.*.options.*' => ['required', 'string', 'min:1', 'max:180'],
            'questions.*.correct_option' => ['required', 'integer', 'between:0,3'],
        ])->validate();

        DB::transaction(function () use ($story, $data): void {
            $story->quizQuestions()->delete();
            foreach ($data['questions'] as $position => $question) {
                $story->quizQuestions()->create($question + ['position' => $position]);
            }
        });

        return $this->respond($request, 'Викторина сохранена. Вопросов: '.count($data['questions']).'.', route('admin.stories.quiz.edit', $story));
    }
}
