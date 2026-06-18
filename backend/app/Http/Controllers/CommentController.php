<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * List approved comments for an event (with replies).
     * GET /api/v1/events/{slug}/comments
     */
    public function index(string $slug): JsonResponse
    {
        $event = Event::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        $comments = Comment::query()
            ->with(['user:id,name', 'replies' => fn ($q) => $q->where('status', 'approved')->with('user:id,name')])
            ->where('event_id', $event->id)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->orderByDesc('is_pinned')
            ->orderBy('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $comments->map(fn (Comment $c) => $this->serializeComment($c, withReplies: true)),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Submit a new comment on an event.
     * POST /api/v1/events/{slug}/comments
     */
    public function store(Request $request, string $slug): JsonResponse
    {
        $event = Event::query()->where('slug', $slug)->where('status', 'published')->firstOrFail();

        $data = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Validate parent belongs to same event
        if (isset($data['parent_id'])) {
            $parent = Comment::query()->find($data['parent_id']);
            if ($parent && $parent->event_id !== $event->id) {
                return response()->json(['message' => 'Parent comment does not belong to this event.'], 422);
            }
        }

        $comment = Comment::query()->create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
            'status' => 'pending', // requires admin approval
        ]);

        return response()->json(['data' => $this->serializeComment($comment->load('user'))], 201);
    }

    /**
     * Delete own comment.
     * DELETE /api/v1/comments/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $comment = Comment::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(Comment $comment, bool $withReplies = false): array
    {
        $data = [
            'id' => $comment->id,
            'body' => $comment->body,
            'status' => $comment->status,
            'is_pinned' => $comment->is_pinned,
            'parent_id' => $comment->parent_id,
            'user' => $comment->relationLoaded('user') ? [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ] : null,
            'created_at' => $comment->created_at?->toJSON(),
        ];

        if ($withReplies && $comment->relationLoaded('replies')) {
            $data['replies'] = $comment->replies->map(fn (Comment $r) => $this->serializeComment($r))->values();
        }

        return $data;
    }
}
