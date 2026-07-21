<?php

namespace App\Enums;

/**
 * PURPOSE: Defines access levels for supplementary files:
 * public, editorial-only, or visible to assigned reviewers.
 *
 * SPECIFICATION: SPEC-07/BR-3, SPEC-07/BR-4
 */
enum ArticleFileVisibility: string
{
    case Public = 'public';
    case EditorialOnly = 'editorial_only';
    case ReviewersOnly = 'reviewers_only';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Публичный доступ',
            self::EditorialOnly => 'Только для редакции',
            self::ReviewersOnly => 'Для рецензентов и редакции',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Public => 'Файл будет доступен всем после публикации статьи',
            self::EditorialOnly => 'Файл виден только редакторам журнала',
            self::ReviewersOnly => 'Файл доступен рецензентам и редакторам',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Public => 'bg-green-100 text-green-700',
            self::EditorialOnly => 'bg-red-100 text-red-700',
            self::ReviewersOnly => 'bg-yellow-100 text-yellow-700',
        };
    }
}
