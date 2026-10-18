<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\DiscussionScope;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Policies\DiscussionPolicy;
use App\Support\ReviewerStats;
use Illuminate\Http\Request;

/**
 * PURPOSE: Handles reviewer actions: listing assigned reviews,
 * viewing review details, accepting, declining, and completing
 * reviews with recommendations and comments.
 *
 * SPECIFICATION: SPEC-03/AC-1, SPEC-03/AC-2, SPEC-03/AC-3, SPEC-03/AC-4
 */
class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $reviews = $user->reviews()
            ->with('article')
            ->orderByDesc('created_at')
            ->get();

        // Active = awaiting work; everything terminal (completed, declined,
        // editor-cancelled) goes to the history list below.
        $activeReviews = $reviews
            ->filter(fn (Review $review) => $review->isPending() || $review->isInProgress())
            ->values();

        $completedReviews = $reviews
            ->filter(fn (Review $review) => ! $review->isPending() && ! $review->isInProgress())
            ->values();

        $stats = ReviewerStats::single($user);

        return view('dashboard.reviews.index', compact('activeReviews', 'completedReviews', 'stats'));
    }

    public function show(Request $request, Review $review)
    {
        $this->authorize('view', $review);

        $review->load('article.category', 'article.responseLetters.uploader');

        // The author's response letter for this review's round (visible on
        // re-review) — only while the reviewer's assignment is active:
        // declined and cancelled reviewers must not read it (SPEC-25/BR-4).
        $responseLetter = $review->status->isActive()
            ? $review->article->responseLetters
                ->where('round', $review->round)
                ->sortByDesc('id')
                ->first()
            : null;

        // The reviewer's own completed review from an earlier round.
        $previousOwnReview = $review->article->reviews()
            ->where('reviewer_id', $request->user()->id)
            ->where('round', '<', $review->round)
            ->where('status', ReviewStatus::Completed)
            ->orderByDesc('completed_at')
            ->first();

        // Discussion threads and the ability to post are precomputed so
        // the view stays declarative — the message form is rendered only
        // for users the discussion policy allows to create messages.
        // The policy is invoked directly (like
        // DiscussionController::authorizeCreate does) because Gate would
        // resolve ArticlePolicy from the article argument.
        $threads = $review->discussions()->root()
            ->with('replies.user.profile', 'user.profile')
            ->latest()
            ->get();
        $canDiscuss = app(DiscussionPolicy::class)->create($request->user(), $review->article, DiscussionScope::Editorial, $review);

        return view('dashboard.reviews.show', compact('review', 'responseLetter', 'previousOwnReview', 'threads', 'canDiscuss'));
    }

    public function update(Request $request, Review $review)
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'recommendation' => 'required|in:accept,minor_revision,major_revision,reject',
            'comments_for_editor' => 'required|string',
            'comments_for_author' => 'required|string',
        ]);

        $review->complete($validated['recommendation'], $validated['comments_for_editor'], $validated['comments_for_author']);

        return redirect()->route('reviews.index')
            ->with('success', 'Рецензия отправлена.');
    }

    public function accept(Request $request, Review $review)
    {
        $this->authorize('accept', $review);

        $review->accept();

        return redirect()->route('reviews.index')
            ->with('success', 'Вы приняли заявку на рецензирование. Дедлайн: '.$review->review_due_at?->format('d.m.Y'));
    }

    public function decline(Request $request, Review $review)
    {
        $this->authorize('decline', $review);

        $review->decline();

        return redirect()->route('reviews.index')
            ->with('info', 'Вы отклонили заявку на рецензирование.');
    }
}
