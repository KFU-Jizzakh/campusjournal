<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Setting;
use App\Models\User;

/**
 * PURPOSE: Decision letter templates (accept / revision / reject) stored
 * as site settings with {placeholders}; renders a concrete letter for an
 * article so the editor starts from a sane default and edits before the
 * final text is snapshotted into decision_comments.
 */
class DecisionLetter
{
    public const DECISIONS = ['accept', 'revision', 'reject'];

    /**
     * Raw template text for a decision from the settings.
     */
    public static function templateFor(string $decision): string
    {
        return (string) Setting::get("decision_template_{$decision}", '');
    }

    /**
     * Template with placeholders substituted by the article's data.
     * Unknown placeholders are left untouched.
     */
    public static function render(string $decision, Article $article, ?User $editor = null): string
    {
        $replacements = [
            '{title}' => $article->title,
            '{id}' => (string) $article->id,
            '{authors}' => $article->authors->pluck('full_name')->filter()->join(', '),
            '{decision_label}' => match ($decision) {
                'accept' => __('dashboard.decision_accept'),
                'revision' => __('dashboard.decision_revision'),
                'reject' => __('dashboard.decision_reject'),
                default => $decision,
            },
            '{editor}' => $editor?->full_name ?? auth()->user()?->full_name ?? '',
        ];

        return trim(strtr(self::templateFor($decision), $replacements));
    }

    /**
     * Rendered letters for all decisions — used to prefill the decision
     * form on the editorial page.
     *
     * @return array<string, string>
     */
    public static function renderAll(Article $article, ?User $editor = null): array
    {
        return collect(self::DECISIONS)
            ->mapWithKeys(fn (string $decision) => [$decision => self::render($decision, $article, $editor)])
            ->all();
    }
}
