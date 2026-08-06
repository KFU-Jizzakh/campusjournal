<?php

namespace App\Console\Commands;

use App\Jobs\DepositArticleToCrossref;
use App\Models\Article;
use App\Services\Doi\DoiMinter;
use Illuminate\Console\Command;

/**
 * PURPOSE: Dispatches Crossref DOI deposits for published
 * articles without a registered DOI. Supports --dry-run.
 *
 * SPECIFICATION: SPEC-08/AC-9
 */
class CrossrefBackfill extends Command
{
    protected $signature = 'crossref:backfill {--dry-run : List candidates without dispatching}';

    protected $description = 'Dispatch Crossref deposits for published articles without a registered DOI';

    public function handle(DoiMinter $minter): int
    {
        if (! $minter->isReady()) {
            $this->error('Crossref is disabled or the prefix is not configured; DOI deposits are disabled.');

            return self::FAILURE;
        }

        $query = Article::query()
            ->published()
            ->whereNull('doi_registered_at');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No articles to backfill.');

            return self::SUCCESS;
        }

        $this->info("Found {$count} articles.");

        if ($this->option('dry-run')) {
            $query->orderBy('id')->chunk(100, function ($articles) {
                foreach ($articles as $article) {
                    $this->line("  [{$article->id}] {$article->title}");
                }
            });

            return self::SUCCESS;
        }

        $dispatched = 0;
        $query->orderBy('id')->chunkById(100, function ($articles) use (&$dispatched) {
            foreach ($articles as $article) {
                DepositArticleToCrossref::dispatch($article);
                $dispatched++;
                usleep(500_000); // ~2 dispatches/sec
            }
        });

        $this->info("Dispatched {$dispatched} deposit jobs.");

        return self::SUCCESS;
    }
}
