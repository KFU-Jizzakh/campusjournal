<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\DiscussionScope;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Discussion;
use App\Models\OutboxEvent;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewDiscussionMessage;
use App\Notifications\NewDiscussionThread;
use App\Policies\DiscussionPolicy;
use Illuminate\Http\Request;

/**
 * PURPOSE: Handles creation, resolution, and reopening of
 * discussion threads with scope-based visibility and
 * participant notifications.
 */
class DiscussionController extends Controller
{
    public function store(Request $request, Article $article)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:discussions,id',
            'review_id' => 'nullable|exists:reviews,id',
            'scope' => 'required|in:article,editorial',
        ]);

        $scope = DiscussionScope::from($validated['scope']);

        // A reply inherits the visibility scope and review link from
        // its parent thread — the posted values are
        // ignored for replies.
        $parent = isset($validated['parent_id'])
            ? Discussion::findOrFail($validated['parent_id'])
            : null;

        if ($parent) {
            $scope = $parent->scope;
            $validated['review_id'] = $parent->review_id;
        }

        $this->authorizeCreate($request->user(), $article, $scope, $parent, $validated['review_id'] ?? null);

        $discussion = Discussion::create([
            'article_id' => $article->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'review_id' => $validated['review_id'] ?? null,
            'scope' => $scope,
            'message' => $validated['message'],
        ]);

        $this->notifyParticipants($discussion, $request->user());

        OutboxEvent::log('discussion.created', $discussion, [
            'article_id' => $article->id,
            'scope' => $scope->value,
            'review_id' => $validated['review_id'] ?? null,
            'message_preview' => mb_substr($validated['message'], 0, 100),
        ]);

        return back()->with('success', 'Сообщение отправлено.');
    }

    public function resolve(Discussion $discussion, Request $request)
    {
        $this->authorize('resolve', $discussion);

        $discussion->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        OutboxEvent::log('discussion.resolved', $discussion, [
            'article_id' => $discussion->article_id,
        ]);

        return back()->with('success', 'Обсуждение закрыто.');
    }

    public function reopen(Discussion $discussion, Request $request)
    {
        $this->authorize('resolve', $discussion);

        $discussion->update([
            'is_resolved' => false,
            'resolved_at' => null,
            'resolved_by' => null,
        ]);

        OutboxEvent::log('discussion.reopened', $discussion, [
            'article_id' => $discussion->article_id,
        ]);

        return back()->with('success', 'Обсуждение переоткрыто.');
    }

    /**
     * PURPOSE: Gates message creation. The review binding is resolved
     * from the explicit review_id or inherited from the parent thread
     * — it is what lets a reviewer write inside the editorial
     * thread of their own active review, and nothing else.
     */
    private function authorizeCreate(User $user, Article $article, DiscussionScope $scope, ?Discussion $parent, ?int $reviewId): void
    {
        $review = null;

        if ($reviewId !== null) {
            $review = Review::findOrFail($reviewId);
            abort_unless($review->article_id === $article->id, 403);
        }

        if ($parent) {
            abort_unless(app(DiscussionPolicy::class)->view($user, $parent), 403);
            abort_if($parent->is_resolved, 403, 'Нельзя отвечать в закрытое обсуждение.');

            $review ??= $parent->review;
        }

        abort_unless(app(DiscussionPolicy::class)->create($user, $article, $scope, $review), 403);
    }

    private function notifyParticipants(Discussion $discussion, User $author): void
    {
        $notification = $discussion->isRoot()
            ? new NewDiscussionThread($discussion)
            : new NewDiscussionMessage($discussion);

        if ($discussion->isRoot()) {
            // Notify all editors who can see this article
            User::permission('manage-submissions')
                ->whereNot('id', $author->id)
                ->where(function ($q) use ($discussion) {
                    $q->whereHas('roles', fn ($q) => $q->whereIn('name', ['editor-in-chief', 'managing-editor']))
                        ->orWhere(function ($q) use ($discussion) {
                            $q->whereHas('roles', fn ($q) => $q->where('name', 'section-editor'))
                                ->where('id', $discussion->article->editor_id);
                        });
                })
                ->each(fn ($user) => $user->notify($notification));
        } else {
            // Reply: notify all participants in this thread
            $participants = Discussion::where('article_id', $discussion->article_id)
                ->where(function ($q) use ($discussion) {
                    $q->where('parent_id', $discussion->parent_id)
                        ->orWhere('id', $discussion->parent_id);
                })
                ->pluck('user_id')
                ->unique()
                ->filter(fn ($id) => $id !== $author->id);

            User::whereIn('id', $participants)->each(fn ($user) => $user->notify($notification));
        }
    }
}
