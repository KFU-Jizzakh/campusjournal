<?php

namespace App\Services\Oai;

use App\Exceptions\Oai\BadArgumentException;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * PURPOSE: Builds Eloquent queries for OAI-PMH ListIdentifiers
 * and ListRecords verbs with metadata format, set, and date-range
 * filtering.
 *
 * SPECIFICATION: SPEC-09/AC-8, SPEC-09/AC-9
 */
class OaiRecordQuery
{
    /**
     * @return array{0: Collection, 1: bool}
     */
    public static function run(array $params): array
    {
        $pageSize = (int) config('oai.page_size', 50);
        $offset = max(0, (int) ($params['offset'] ?? 0));

        $query = Article::query()->withTrashed();

        // Only published articles (trashed ones are tombstones of previously-published articles,
        // so allow trashed regardless of status).
        $query->where(function (Builder $q) {
            $q->published()
                ->orWhereNotNull('deleted_at');
        });

        if (! empty($params['from'])) {
            $query->where('updated_at', '>=', self::parseDate($params['from'], 'from'));
        }

        if (! empty($params['until'])) {
            $query->where('updated_at', '<=', self::parseDate($params['until'], 'until'));
        }

        if (! empty($params['set'])) {
            self::applySet($query, (string) $params['set']);
        }

        $articles = $query->orderBy('id')
            ->skip($offset)
            ->take($pageSize + 1)
            ->get();

        $hasMore = $articles->count() > $pageSize;
        if ($hasMore) {
            $articles = $articles->slice(0, $pageSize)->values();
        }

        return [$articles, $hasMore];
    }

    private static function applySet(Builder $query, string $set): void
    {
        if (str_starts_with($set, 'category:')) {
            $slug = substr($set, strlen('category:'));
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $slug));

            return;
        }

        if (str_starts_with($set, 'issue:')) {
            $id = substr($set, strlen('issue:'));
            if (! ctype_digit($id)) {
                throw new BadArgumentException('Invalid set.');
            }
            $query->where('issue_id', (int) $id);

            return;
        }

        throw new BadArgumentException('Unknown set.');
    }

    private static function parseDate(string $value, string $field): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            throw new BadArgumentException("Invalid {$field} date.");
        }
    }
}
