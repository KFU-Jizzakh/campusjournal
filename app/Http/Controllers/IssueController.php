<?php

namespace App\Http\Controllers;

use App\Models\Issue;

/**
 * PURPOSE: Serves published issue listings and individual
 * issue detail pages with their published articles.
 * Non-published issues are hidden from the public site.
 *
 * SPECIFICATION: SPEC-24/AC-1, SPEC-24/AC-2, SPEC-24/BR-2
 */
class IssueController extends Controller
{
    /**
     * PURPOSE: Lists published issues ordered by year and number.
     *
     * SPECIFICATION: SPEC-24/AC-1
     */
    public function index()
    {
        $issues = Issue::published()
            ->orderByDesc('year')
            ->orderByDesc('number')
            ->paginate(12);

        return view('issues.index', compact('issues'));
    }

    /**
     * PURPOSE: Serves a published issue page with its published
     * articles; planned and in-progress issues return 404.
     *
     * SPECIFICATION: SPEC-24/AC-2, SPEC-24/BR-2
     */
    public function show(Issue $issue)
    {
        abort_unless($issue->isPublished(), 404);

        $issue->load(['articles' => function ($query) {
            $query->published()->with('authors', 'category');
        }]);

        return view('issues.show', compact('issue'));
    }
}
