<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Review;
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
        $reviews = $request->user()->reviews()
            ->with('article')
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.reviews.index', compact('reviews'));
    }

    public function show(Request $request, Review $review)
    {
        $this->authorize('view', $review);

        $review->load('article.category');

        return view('dashboard.reviews.show', compact('review'));
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
