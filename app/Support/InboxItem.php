<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * PURPOSE: A single task-oriented dashboard inbox entry: which object
 * needs attention, what has to be done, how urgent it is, and where
 * to act. Rendered by the dashboard "Требуют внимания" section.
 */
readonly class InboxItem
{
    public function __construct(
        public string $title,
        public string $task,
        public string $url,
        public string $actionLabel,
        public string $urgency = 'normal',
        public ?string $deadlineLabel = null,
        public ?string $badgeLabel = null,
        public ?string $badgeColor = null,
        public ?InboxAction $primaryForm = null,
        public ?InboxAction $secondaryForm = null,
        public ?Carbon $sortDate = null,
    ) {}

    /**
     * Left stripe color (palette of x-status-badge).
     */
    public function urgencyColor(): string
    {
        return match ($this->urgency) {
            'overdue', 'urgent' => 'danger',
            'warning' => 'warning',
            default => 'gray',
        };
    }

    public function stripeClass(): string
    {
        return match ($this->urgency) {
            'overdue', 'urgent' => 'border-l-red-400',
            'warning' => 'border-l-yellow-400',
            default => 'border-l-gray-200',
        };
    }

    public function urgencyRank(): int
    {
        return match ($this->urgency) {
            'overdue' => 0,
            'urgent' => 1,
            'warning' => 2,
            default => 3,
        };
    }

    /**
     * Ordering shared by inbox-like lists: urgency first, then the
     * driving date (deadline / waiting-since) ascending, undated last.
     */
    public static function sortCompare(InboxItem $a, InboxItem $b): int
    {
        $rank = $a->urgencyRank() <=> $b->urgencyRank();

        if ($rank !== 0) {
            return $rank;
        }

        $aTs = $a->sortDate?->getTimestamp();
        $bTs = $b->sortDate?->getTimestamp();

        if ($aTs === $bTs) {
            return 0;
        }

        if ($aTs === null) {
            return 1;
        }

        if ($bTs === null) {
            return -1;
        }

        return $aTs <=> $bTs;
    }
}
