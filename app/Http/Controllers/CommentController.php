<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Models\Comment;
use App\Models\Story;
use App\Support\GuestInteraction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    use RespondsToAjax;

    public function store(Request $request, Story $story): RedirectResponse|JsonResponse
    {
        abort_unless($story->status === 'published' && $story->published_at?->isPast(), 404);
        $data = $request->validate(['body' => ['required', 'string', 'min:3', 'max:2000']]);
        $guestName = GuestInteraction::guestName($request);
        Comment::create(['story_id' => $story->id, 'user_id' => $request->user()?->id, 'guest_name' => $guestName, 'body' => trim($data['body']), 'status' => 'pending']);
        return $this->respond($request, 'Спасибо! Комментарий появится после проверки.', route('stories.show', $story), false);
    }

    public function vote(Request $request, Comment $comment): RedirectResponse|JsonResponse
    {
        abort_unless($comment->status === 'approved' && $comment->story()->where('status', 'published')->where('published_at', '<=', now())->exists(), 404);
        $data = $request->validate(['vote' => ['required', Rule::in(['like', 'dislike'])]]);
        DB::transaction(function () use ($comment, $request, $data): void {
            $locked = Comment::query()->lockForUpdate()->findOrFail($comment->id);
            $identity = GuestInteraction::identity($request);
            $current = $locked->votes()->where(GuestInteraction::lookup($request))->first();
            if ($current?->vote === $data['vote']) {
                $locked->decrement($data['vote'] === 'like' ? 'likes_count' : 'dislikes_count');
                $current->delete();
                return;
            }
            if ($current) {
                $locked->decrement($current->vote === 'like' ? 'likes_count' : 'dislikes_count');
                $current->update(['vote' => $data['vote']]);
            } else {
                $locked->votes()->create($identity + ['vote' => $data['vote']]);
            }
            $locked->increment($data['vote'] === 'like' ? 'likes_count' : 'dislikes_count');
        });
        if ($request->expectsJson()) {
            $comment->refresh();
            return response()->json(['message' => 'Ваш голос сохранён.', 'comment_votes' => ['like' => $comment->likes_count, 'dislike' => $comment->dislikes_count], 'current_vote' => $comment->votes()->where(GuestInteraction::lookup($request))->value('vote')]);
        }
        return $this->respond($request, 'Ваш голос сохранён.', route('stories.show', $comment->story->slug).'#comment-'.$comment->id, false);
    }
}
