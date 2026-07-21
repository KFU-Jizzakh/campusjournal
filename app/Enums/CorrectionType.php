<?php

namespace App\Enums;

/**
 * PURPOSE: Defines the types of post-publication corrections
 * that can be attached to a published article.
 *
 * SPECIFICATION: SPEC-16/BR-6
 */
enum CorrectionType: string
{
    case Corrigendum = 'corrigendum';
    case Erratum = 'erratum';
    case ExpressionOfConcern = 'expression_of_concern';

    public function label(): string
    {
        return match ($this) {
            self::Corrigendum => 'Корригендум',
            self::Erratum => 'Эрратум',
            self::ExpressionOfConcern => 'Выражение обеспокоенности',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Corrigendum => 'bg-yellow-50 text-yellow-700',
            self::Erratum => 'bg-orange-50 text-orange-700',
            self::ExpressionOfConcern => 'bg-red-50 text-red-700',
        };
    }
}
