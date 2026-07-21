<?php

namespace App\Enums;

/**
 * PURPOSE: Defines the review anonymity model:
 * single-blind (default), double-blind, or open.
 *
 * SPECIFICATION: SPEC-05/BR-1, SPEC-05/BR-4
 */
enum ReviewType: string
{
    case SingleBlind = 'single_blind';

    case DoubleBlind = 'double_blind';

    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::SingleBlind => 'Одностороннее слепое',
            self::DoubleBlind => 'Двойное слепое',
            self::Open => 'Открытое',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SingleBlind => 'info',
            self::DoubleBlind => 'warning',
            self::Open => 'success',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::SingleBlind => 'bg-blue-50 text-blue-700',
            self::DoubleBlind => 'bg-yellow-50 text-yellow-700',
            self::Open => 'bg-green-50 text-green-700',
        };
    }
}
