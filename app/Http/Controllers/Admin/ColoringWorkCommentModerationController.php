<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\ColoringWorkComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ColoringWorkCommentModerationController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $comments = ColoringWorkComment::query()->with(['submission', 'coloringPage.category', 'user:id,name'])->where('status', 'pending')->oldest()->paginate(25);
        return view('admin.colorings.comments', compact('comments'));
    }

    public function update(Request $request, ColoringWorkComment $comment): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $comment->update(['status' => $data['status'], 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        return $this->respond($request, 'Решение по комментарию сохранено.');
    }
}
