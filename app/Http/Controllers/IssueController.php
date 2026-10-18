<?php

namespace App\Http\Controllers;

use App\Models\Issue;

/**
 * PURPOSE: Serves published issue listings and individual
 * issue detail pages with their published articles.
 * Non-published issues are hidden from the public site.
 */
class IssueController extends Controller
{
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
