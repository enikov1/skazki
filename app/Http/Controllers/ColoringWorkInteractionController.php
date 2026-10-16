<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\ColoringSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ColoringWorkInteractionController extends Controller
{
    use RespondsToAjax;

    public function like(Request $request, string $submission): JsonResponse|RedirectResponse
    {
        $work = ColoringSubmission::query()->where('slug', $submission)->where('status', 'approved')->firstOrFail();
        $like = $request->user()->coloringWorkLikes()->where('coloring_submission_id', $work->id)->first();
        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            $request->user()->coloringWorkLikes()->create(['coloring_submission_id' => $work->id]);
            $liked = true;
        }
        $count = $work->likes()->count();
        return $request->expectsJson()
            ? response()->json(['message' => $liked ? 'Вы отметили работу.' : 'Отметка снята.', 'liked' => $liked, 'count' => $count])
            : $this->respond($request, $liked ? 'Вы отметили работу.' : 'Отметка снята.');
    }

    public function comment(Request $request, string $submission): JsonResponse|RedirectResponse
    {
        $work = ColoringSubmission::query()->where('slug', $submission)->where('status', 'approved')->firstOrFail();
        $data = $request->validate(['body' => ['required', 'string', 'min:3', 'max:1500']]);
        $work->comments()->create(['user_id' => $request->user()->id, 'body' => trim($data['body']), 'status' => 'pending']);
        return $request->expectsJson()
            ? response()->json(['message' => 'Комментарий отправлен на проверку.'])
            : $this->respond($request, 'Комментарий отправлен на проверку.');
    }
}
