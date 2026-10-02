<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Issue;
use App\Support\DashboardInbox;
use App\Support\DashboardWatchlist;
use Illuminate\Http\Request;

/**
 * PURPOSE: Dashboard homepage showing the task-oriented inbox,
 * the review-deadline and watchlist overviews for editors, the
 * user's submitted articles, active reviews, and editorial
 * workload statistics.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $showEditorial = DashboardInbox::canManageSubmissions($user);

        $inbox = DashboardInbox::for($user);

        $reviewDeadlines = $showEditorial ? DashboardWatchlist::deadlines($user) : collect();

        $watchlist = $showEditorial ? DashboardWatchlist::for($user) : collect();

        $issueAssembly = null;

        if (DashboardInbox::can($user, 'publish-issue')) {
            $issueAssembly = [
                'issue' => Issue::published()->withCount('articles')->latest('published_at')->first(),
                'ready' => Article::query()
                    ->whereIn('status', [ArticleStatus::Accepted, ArticleStatus::Approved])
                    ->whereNull('issue_id')
                    ->orderBy('submitted_at')
                    ->get(),
            ];
        }

        $search = $request->query('q');

        $myArticles = $user->submittedArticles()
            ->with('category', 'issue')
            ->when($search, fn ($query, $term) => $query->where(fn ($inner) => $inner
                ->where('title', 'ilike', "%{$term}%")
                ->orWhereHas('authors', fn ($authors) => $authors->where('full_name', 'ilike', "%{$term}%"))))
            ->orderByDesc('created_at')
            ->get();

        $coauthoredArticles = $user->coauthoredArticles()
            ->with('category', 'issue')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Article $article) => [
                'article' => $article,
                'url' => $user->can('view', $article) ? route('submissions.show', $article) : null,
            ]);

        $myReviews = $user->reviews()
            ->with('article')
            ->where('status', '!=', ReviewStatus::Completed)
            ->orderByDesc('created_at')
            ->get();

        $editorialCounts = null;

        if ($showEditorial) {
            $editorialCounts = DashboardInbox::editorialArticles($user)
                ->selectRaw('sum(case when status = ? and editor_id is null then 1 else 0 end) as new_submissions', [ArticleStatus::Submitted->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as in_review', [ArticleStatus::InReview->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as accepted', [ArticleStatus::Accepted->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as copyediting', [ArticleStatus::Copyediting->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as production', [ArticleStatus::Production->value])
                ->first();
        }

        return view('dashboard.index', compact(
            'inbox',
            'reviewDeadlines',
            'watchlist',
            'showEditorial',
            'issueAssembly',
            'myArticles',
            'coauthoredArticles',
            'myReviews',
            'editorialCounts',
            'search',
        ));
    }
}
