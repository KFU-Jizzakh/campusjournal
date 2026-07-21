<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\DiscussionScope;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Discussion;
use App\Models\OutboxEvent;
use App\Models\User;
use App\Notifications\NewDiscussionMessage;
use App\Notifications\NewDiscussionThread;
use App\Policies\DiscussionPolicy;
use Illuminate\Http\Request;

/**
 * PURPOSE: Handles creation, resolution, and reopening of
 * discussion threads with scope-based visibility and
 * participant notifications.
 *
 * SPECIFICATION: SPEC-06/AC-1, SPEC-06/AC-3, SPEC-06/AC-4, SPEC-06/AC-8
 */
class DiscussionController extends Controller
{
    public function index(Article $article, Request $request)
    {
        $this->authorize('viewEditorial', $article);

        $user = $request->user();

        $discussions = $article->discussions()
            ->root()
            ->with(['user.profile', 'replies.user.profile', 'review'])
            ->latest()
            ->paginate(20);

        // Filter client-side by visibility — only show discussions the user can see
        $discussions->setCollection(
            $discussions->getCollection()->filter(fn ($d) => $d->isVisibleTo($user))
        );

        return view('dashboard.editorial.partials.discussions', compact('article', 'discussions'));
    }

    public function store(Request $request, Article $article)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:discussions,id',
            'review_id' => 'nullable|exists:reviews,id',
            'scope' => 'required|in:article,editorial',
        ]);

        $scope = DiscussionScope::from($validated['scope']);

        $this->authorizeCreate($request->user(), $article, $scope, $validated['parent_id'] ?? null);

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

    private function authorizeCreate(User $user, Article $article, DiscussionScope $scope, ?int $parentId): void
    {
        if ($parentId) {
            $parent = Discussion::findOrFail($parentId);
            abort_unless(app(DiscussionPolicy::class)->view($user, $parent), 403);
            abort_if($parent->is_resolved, 403, 'Нельзя отвечать в закрытое обсуждение.');
        }

        abort_unless(app(DiscussionPolicy::class)->create($user, $article, $scope), 403);
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
