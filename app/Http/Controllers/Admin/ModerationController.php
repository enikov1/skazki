<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsToAjax;
use Illuminate\Http\JsonResponse;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModerationController extends Controller
{
    use RespondsToAjax;
    public function index(): View
    {
        Gate::authorize('viewAny', Comment::class);
        return view('admin.moderation.index', ['comments' => Comment::with(['story:id,title,slug', 'user:id,name'])->where('status', 'pending')->latest()->paginate(25)]);
    }

    public function update(Request $request, Comment $comment): RedirectResponse|JsonResponse
    {
        Gate::authorize('moderate', $comment);
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $comment->update(['status' => $data['status'], 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        return $this->respond($request, 'Решение по комментарию сохранено.');
    }
}
