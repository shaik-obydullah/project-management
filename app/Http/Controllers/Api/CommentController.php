<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Issue;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $comments = $issue->comments()
            ->with(['user:id,name,email,avatar', 'replies.user:id,name,email,avatar'])
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['comments' => $comments]);
    }

    public function store(Request $request, Issue $issue)
    {
        abort_unless($issue->project->isMember($request->user()), 403, 'You are not a member of this project.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);

        $comment = Comment::create([
            'issue_id' => $issue->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        // Extract @mentions and notify
        preg_match_all('/@(\w+)/', $validated['body'], $matches);
        if (! empty($matches[1])) {
            $usernames = array_unique($matches[1]);
            $usersToNotify = $issue->project->members()
                ->whereIn('users.name', $usernames)
                ->where('users.id', '!=', $request->user()->id)
                ->get();

            foreach ($usersToNotify as $user) {
                $user->notify(new \App\Notifications\CommentMention($issue, $request->user(), $validated['body']));
            }
        }

        activity()
            ->causedBy($request->user())
            ->performedOn($issue)
            ->event('commented')
            ->log('added a comment');

        $comment->load('user:id,name,email,avatar');

        return response()->json([
            'message' => 'Comment added',
            'comment' => $comment,
        ], 201);
    }

    public function update(Request $request, Comment $comment)
    {
        abort_unless($comment->user_id === $request->user()->id || $comment->issue->project->isAdmin($request->user()), 403, 'You cannot edit this comment.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => 'Comment updated',
            'comment' => $comment->load('user:id,name,email,avatar'),
        ]);
    }

    public function destroy(Request $request, Comment $comment)
    {
        abort_unless($comment->user_id === $request->user()->id || $comment->issue->project->isAdmin($request->user()), 403, 'You cannot delete this comment.');

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}