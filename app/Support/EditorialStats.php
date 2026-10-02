<?php

namespace App\Support;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;

/**
 * PURPOSE: Journal-level editorial aggregates — pipeline funnel,
 * average time to decision, reviewer turnaround, and per-section-editor
 * workload — powering the analytics page and the assignment forms.
 */
class EditorialStats
{
    /**
     * Pipeline funnel: counts of non-draft articles per status,
     * keyed by the status value (zero-filled).
     *
     * @return array<string, int>
     */
    public static function funnel(): array
    {
        $rows = Article::submitted()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [];

        foreach (ArticleStatus::cases() as $status) {
            $counts[$status->value] = (int) ($rows[$status->value] ?? 0);
        }

        return $counts;
    }

    /**
     * Average days from submission to the first editorial decision.
     */
    public static function avgDaysToDecision(): ?int
    {
        $avg = Article::submitted()
            ->whereNotNull('decided_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (decided_at - submitted_at))) as avg_seconds')
            ->value('avg_seconds');

        return $avg === null ? null : (int) round(((float) $avg) / 86400);
    }

    /**
     * Average days from invitation to a submitted review (completed only).
     */
    public static function avgReviewerTurnaround(): ?int
    {
        $avg = Review::query()
            ->where('status', ReviewStatus::Completed)
            ->whereNotNull('assigned_at')
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - assigned_at))) as avg_seconds')
            ->value('avg_seconds');

        return $avg === null ? null : (int) round(((float) $avg) / 86400);
    }

    /**
     * Active (not yet resolved) article count per section editor.
     *
     * @return array<int, array{user: User, active: int}>
     */
    public static function sectionEditorLoad(): array
    {
        $editors = User::role('section-editor')->with('profile')->get();

        $counts = Article::submitted()
            ->whereNotNull('editor_id')
            ->whereIn('status', [
                ArticleStatus::Submitted,
                ArticleStatus::InReview,
                ArticleStatus::Revision,
                ArticleStatus::Accepted,
                ArticleStatus::Copyediting,
                ArticleStatus::Production,
                ArticleStatus::AwaitingApproval,
                ArticleStatus::Approved,
            ])
            ->groupBy('editor_id')
            ->selectRaw('editor_id, COUNT(*) as aggregate')
            ->pluck('aggregate', 'editor_id');

        return $editors
            ->map(fn (User $editor) => [
                'user' => $editor,
                'active' => (int) ($counts[$editor->id] ?? 0),
            ])
            ->all();
    }
}
