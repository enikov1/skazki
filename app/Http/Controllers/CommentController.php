<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Story;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    public function store(Request $request, Story $story): RedirectResponse
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:3', 'max:2000']]);
        Comment::create(['story_id' => $story->id, 'user_id' => $request->user()->id, 'body' => trim($data['body']), 'status' => 'pending']);
        return redirect()->route('stories.show', $story)->with('status', 'Спасибо! Комментарий появится после проверки.');
    }

    public function vote(Request $request, Comment $comment): RedirectResponse
    {
        abort_unless($comment->status === 'approved' && $comment->story()->where('status', 'published')->where('published_at', '<=', now())->exists(), 404);
        $data = $request->validate(['vote' => ['required', Rule::in(['like', 'dislike'])]]);
        DB::transaction(function () use ($comment, $request, $data): void {
            $locked = Comment::query()->lockForUpdate()->findOrFail($comment->id);
            $current = $locked->votes()->where('user_id', $request->user()->id)->first();
            if ($current?->vote === $data['vote']) {
                $locked->decrement($data['vote'] === 'like' ? 'likes_count' : 'dislikes_count');
                $current->delete();
                return;
            }
            if ($current) {
                $locked->decrement($current->vote === 'like' ? 'likes_count' : 'dislikes_count');
                $current->update(['vote' => $data['vote']]);
            } else {
                $locked->votes()->create(['user_id' => $request->user()->id, 'vote' => $data['vote']]);
            }
            $locked->increment($data['vote'] === 'like' ? 'likes_count' : 'dislikes_count');
        });
        return redirect()->to(route('stories.show', $comment->story->slug).'#comment-'.$comment->id);
    }
}
