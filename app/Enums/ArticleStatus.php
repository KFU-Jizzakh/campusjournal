<?php

namespace App\Enums;

/**
 * PURPOSE: Defines the article editorial workflow state machine
 * with labels, colours, and allowed status transitions.
 *
 * SPECIFICATION: SPEC-01/BR-5, SPEC-02/BR-6, SPEC-04/BR-5, SPEC-13/BR-1, SPEC-16/BR-1, SPEC-16/BR-2
 */
enum ArticleStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Accepted = 'accepted';
    case Revision = 'revision';
    case Rejected = 'rejected';
    case Copyediting = 'copyediting';
    case Production = 'production';
    case AwaitingApproval = 'awaiting_approval';
    case Approved = 'approved';
    case Published = 'published';
    case Withdrawn = 'withdrawn';
    case Retracted = 'retracted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Черновик',
            self::Submitted => 'Подана',
            self::InReview => 'Рецензирование',
            self::Revision => 'Доработка',
            self::Accepted => 'Принята',
            self::Copyediting => 'На корректуре',
            self::Production => 'В производстве',
            self::AwaitingApproval => 'На утверждении автора',
            self::Approved => 'Утверждено автором',
            self::Rejected => 'Отклонена',
            self::Published => 'Опубликована',
            self::Withdrawn => 'Отозвана',
            self::Retracted => 'Ретрекшн',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted, self::Copyediting, self::AwaitingApproval => 'info',
            self::InReview, self::Production => 'warning',
            self::Rejected, self::Revision, self::Retracted, self::Withdrawn => 'danger',
            self::Accepted, self::Approved, self::Published => 'success',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::InReview, self::Withdrawn],
            self::InReview => [self::Accepted, self::Revision, self::Rejected, self::Withdrawn],
            self::Revision => [self::Submitted, self::Withdrawn],
            self::Accepted => [self::Copyediting, self::Withdrawn],
            self::Copyediting => [self::Production, self::Withdrawn],
            self::Production => [self::AwaitingApproval, self::Withdrawn],
            self::AwaitingApproval => [self::Production, self::Approved, self::Withdrawn],
            self::Approved => [self::Published, self::Withdrawn],
            self::Rejected => [],
            self::Published => [self::Retracted],
            self::Withdrawn => [],
            self::Retracted => [],
        };
    }

    /**
     * Whether the article has been approved by the author
     * and is ready for publication.
     */
    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
