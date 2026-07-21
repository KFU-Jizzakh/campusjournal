<?php

namespace App\Http\Controllers;

use App\Models\Issue;

/**
 * PURPOSE: Serves published issue listings and individual
 * issue detail pages with their published articles.
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

    public function show(Issue $issue)
    {
        abort_unless($issue->status === 'published', 404);

        $issue->load(['articles' => function ($query) {
            $query->published()->with('authors', 'category');
        }]);

        return view('issues.show', compact('issue'));
    }
}
