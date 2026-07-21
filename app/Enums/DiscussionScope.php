<?php

namespace App\Enums;

/**
 * PURPOSE: Defines discussion visibility scope: article-wide
 * (author + editors) or editorial-only (editors only).
 *
 * SPECIFICATION: SPEC-06/BR-2, SPEC-06/BR-3
 */
enum DiscussionScope: string
{
    case Article = 'article';
    case Editorial = 'editorial';

    public function label(): string
    {
        return match ($this) {
            self::Article => 'Общее',
            self::Editorial => 'Редакционное',
        };
    }
}
