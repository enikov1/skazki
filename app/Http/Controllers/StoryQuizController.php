<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\StoryQuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoryQuizController extends Controller
{
    public function show(Story $story): View
    {
        $this->ensurePublished($story);
        $questions = $story->quizQuestions()->orderBy('position')->get();
        abort_if($questions->isEmpty(), 404);
        $result = null;
        if (request()->filled('result')) {
            $result = StoryQuizAttempt::query()->where('story_id', $story->id)->find(request()->integer('result'));
            abort_if(! $result, 404);
            abort_if($result->user_id !== null && $result->user_id !== auth()->id(), 404);
        }

        return view('stories.quiz', compact('story', 'questions', 'result'));
    }

    public function submit(Request $request, Story $story): RedirectResponse|JsonResponse
    {
        $this->ensurePublished($story);
        $questions = $story->quizQuestions()->orderBy('position')->get();
        abort_if($questions->isEmpty(), 404);
        $data = $request->validate([
            'answers' => ['required', 'array', 'size:'.$questions->count()],
            'answers.*' => ['required', 'integer', 'between:0,3'],
        ]);

        $score = 0;
        foreach ($questions as $index => $question) {
            if ((int) $data['answers'][$index] === $question->correct_option) {
                $score++;
            }
        }
        $attempt = StoryQuizAttempt::create([
            'story_id' => $story->id,
            'user_id' => $request->user()?->id,
            'score' => $score,
            'question_count' => $questions->count(),
            'answers' => $data['answers'],
        ]);
        $message = "Вы ответили верно на {$score} из {$questions->count()} вопросов.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'redirect' => route('stories.quiz', $story).'?result='.$attempt->id, 'score' => $score, 'total' => $questions->count()]);
        }

        return redirect()->route('stories.quiz', [$story, 'result' => $attempt->id])->with('status', $message);
    }

    private function ensurePublished(Story $story): void
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
    }
}
