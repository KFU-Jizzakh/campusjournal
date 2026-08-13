<?php

namespace App\Console\Commands;

use App\Enums\ArticleStatus;
use App\Jobs\DepositArticleToCrossref;
use App\Models\Article;
use App\Services\Doi\DoiMinter;
use Illuminate\Console\Command;

/**
 * PURPOSE: Dispatches Crossmark re-deposits for registered articles
 * whose retraction/correction updates were skipped while the
 * CROSSMARK_POLICY_DOI was missing or invalid. Run it once the
 * policy DOI has been configured to backfill skipped updates.
 * Supports --dry-run.
 *
 * SPECIFICATION: SPEC-16/AC-5, SPEC-16/BR-7
 */
class CrossrefRedeposit extends Command
{
    protected $signature = 'crossref:redeposit {--dry-run : List candidates without dispatching}';

    protected $description = 'Dispatch Crossref Crossmark re-deposits for retractions and corrections that were skipped';

    public function handle(DoiMinter $minter): int
    {
        if (! $minter->isReady()) {
            $this->error('Crossref is disabled or the prefix is not configured; re-deposits are disabled.');

            return self::FAILURE;
        }

        if (config('services.crossref.crossmark.policy_doi') === null) {
            $this->error('CROSSMARK_POLICY_DOI is not configured; configure it before running the backfill.');

            return self::FAILURE;
        }

        $query = Article::query()
            ->whereNotNull('doi_registered_at')
            ->where(function ($q) {
                $q->where('status', ArticleStatus::Retracted)->orHas('corrections');
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No articles to re-deposit.');

            return self::SUCCESS;
        }

        $this->info("Found {$count} articles.");

        if ($this->option('dry-run')) {
            $query->orderBy('id')->chunk(100, function ($articles) {
                foreach ($articles as $article) {
                    $this->line("  [{$article->id}] {$article->title} ({$this->updateTypeFor($article)})");
                }
            });

            return self::SUCCESS;
        }

        $dispatched = 0;
        $query->orderBy('id')->chunkById(100, function ($articles) use (&$dispatched) {
            foreach ($articles as $article) {
                DepositArticleToCrossref::dispatch($article, null, $this->updateTypeFor($article));
                $dispatched++;
                usleep(500_000); // ~2 dispatches/sec
            }
        });

        $this->info("Dispatched {$dispatched} re-deposit jobs.");

        return self::SUCCESS;
    }

    /**
     * PURPOSE: Chooses the Crossmark update type for an article:
     * `retraction` for retracted articles (the builder appends the
     * correction updates before the retraction update), `correction`
     * otherwise.
     *
     * SPECIFICATION: SPEC-16/AC-5, SPEC-16/BR-7
     */
    private function updateTypeFor(Article $article): string
    {
        return $article->isRetracted() ? 'retraction' : 'correction';
    }
}
