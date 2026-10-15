<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.moderation.index', ['comments' => Comment::with(['story:id,title,slug', 'user:id,name'])->where('status', 'pending')->latest()->paginate(25)]);
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $comment->update(['status' => $data['status'], 'moderated_by' => $request->user()->id, 'moderated_at' => now()]);
        return back()->with('status', 'Решение по комментарию сохранено.');
    }
}
