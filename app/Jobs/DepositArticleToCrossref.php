<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\CrossrefDeposit;
use App\Models\OutboxEvent;
use App\Services\Doi\CrossrefClient;
use App\Services\Doi\CrossrefXmlBuilder;
use App\Services\Doi\DoiMinter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * PURPOSE: Queued job for depositing a published article's DOI
 * metadata to Crossref with 3 retries and exponential backoff.
 * Supports Crossmark update deposits (retraction/correction).
 *
 * SPECIFICATION: SPEC-08/AC-1, SPEC-08/AC-6, SPEC-08/AC-7, SPEC-08/AC-8, SPEC-08/AC-9, SPEC-08/BR-2c, SPEC-08/BR-3, SPEC-08/BR-4, SPEC-16/AC-5, SPEC-16/BR-7, SPEC-16/BR-8
 */
class DepositArticleToCrossref implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public Article $article,
        public ?int $actorId = null,
        public ?string $updateType = null,
    ) {}

    public function handle(DoiMinter $minter, CrossrefXmlBuilder $builder, CrossrefClient $client): void
    {
        // Always work from a freshly fetched article, regardless of how
        // handle() was invoked (queued jobs are re-fetched by
        // SerializesModels; direct calls may pass a stale instance).
        $this->article = Article::findOrFail($this->article->id);

        // Reuse the DOI reserved in a previous deposit record when the
        // article row has none yet (e.g. the early persist failed and
        // this is a retry) — the suffix must never change between
        // deposit attempts (SPEC-08/BR-2c).
        $doi = $this->article->latestCrossrefDeposit?->doi
            ?? $minter->mint($this->article);

        // Persist the minted DOI before the first deposit attempt so that
        // retries (60/300/900s) reuse the same suffix instead of minting a
        // new one. If this write fails (e.g. DB outage), the deposit still
        // proceeds with the minted DOI and the success path persists it.
        $persisted = $this->article->doi === $doi;

        if (! $persisted) {
            try {
                $this->article->forceFill(['doi' => $doi])->save();
                $persisted = true;
            } catch (Throwable $e) {
                report($e);
            }
        }

        $batchId = (string) Str::uuid();
        $xml = $builder->build($this->article->refresh(), $batchId, $this->updateType, $doi);

        $deposit = CrossrefDeposit::create([
            'article_id' => $this->article->id,
            'doi' => $doi,
            'batch_id' => $batchId,
            'xml_payload' => $xml,
            'status' => CrossrefDeposit::STATUS_PENDING,
            'attempted_by' => $this->actorId,
        ]);

        try {
            $response = $client->deposit($xml, $batchId);
        } catch (Throwable $e) {
            $deposit->update([
                'status' => CrossrefDeposit::STATUS_FAILED,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $accepted = $response->successful();

        $deposit->update([
            'status' => $accepted ? CrossrefDeposit::STATUS_ACCEPTED : CrossrefDeposit::STATUS_FAILED,
            'http_status' => $response->status(),
            'response_body' => $response->body(),
        ]);

        if ($accepted) {
            // In the degraded path (early persist failed) the DOI must be
            // written together with the registration date; otherwise the
            // article would end up registered at Crossref with no DOI stored.
            $this->article->update($persisted
                ? ['doi_registered_at' => now()]
                : ['doi' => $doi, 'doi_registered_at' => now()]);

            OutboxEvent::log('article.doi_deposited', $this->article, [
                'doi' => $doi,
                'batch_id' => $batchId,
            ]);

            return;
        }

        throw new \RuntimeException("Crossref deposit failed with HTTP {$response->status()}");
    }
}
