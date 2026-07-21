<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;

/**
 * PURPOSE: Dashboard homepage showing the user's submitted
 * articles, active reviews, and editorial workload overview.
 */
class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $myArticles = $user->submittedArticles()
            ->with('category', 'issue')
            ->orderByDesc('created_at')
            ->get();

        $myReviews = $user->reviews()
            ->with('article')
            ->where('status', '!=', ReviewStatus::Completed)
            ->orderByDesc('created_at')
            ->get();

        $editorialCounts = null;

        if ($user->hasPermissionTo('manage-submissions')) {
            $query = Article::submitted();

            if ($user->hasRole('section-editor') && ! $user->hasAnyRole(['editor-in-chief', 'managing-editor'])) {
                $query->where('editor_id', $user->id);
            }

            $editorialCounts = $query
                ->selectRaw('sum(case when status = ? and editor_id is null then 1 else 0 end) as new_submissions', [ArticleStatus::Submitted->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as in_review', [ArticleStatus::InReview->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as accepted', [ArticleStatus::Accepted->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as copyediting', [ArticleStatus::Copyediting->value])
                ->selectRaw('sum(case when status = ? then 1 else 0 end) as production', [ArticleStatus::Production->value])
                ->first();
        }

        return view('dashboard.index', compact('myArticles', 'myReviews', 'editorialCounts'));
    }
}
