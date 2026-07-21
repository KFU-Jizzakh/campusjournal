<?php

namespace App\Enums;

/**
 * PURPOSE: Defines the review lifecycle state machine
 * with labels, colours, and allowed status transitions.
 *
 * SPECIFICATION: SPEC-03/BR-1, SPEC-03/BR-2
 */
enum ReviewStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ожидает',
            self::InProgress => 'В работе',
            self::Completed => 'Завершена',
            self::Declined => 'Отклонена',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::InProgress => 'info',
            self::Completed => 'success',
            self::Declined => 'danger',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress, self::Declined],
            self::InProgress => [self::Completed],
            self::Completed, self::Declined => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
