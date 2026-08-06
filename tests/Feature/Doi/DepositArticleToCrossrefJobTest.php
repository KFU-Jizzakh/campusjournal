<?php

use App\Exceptions\DoiPrefixNotConfiguredException;
use App\Jobs\DepositArticleToCrossref;
use App\Models\Article;
use App\Models\CrossrefDeposit;
use App\Models\Issue;
use App\Services\Doi\CrossrefClient;
use App\Services\Doi\CrossrefXmlBuilder;
use App\Services\Doi\DoiMinter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.crossref.enabled' => true,
        'services.crossref.endpoint' => 'https://test.crossref.org/servlet/deposit',
        'services.crossref.prefix' => '10.12345',
        'services.crossref.username' => 'u',
        'services.crossref.password' => 'p',
    ]);
});

test('successful deposit records accepted status and sets doi_registered_at', function () {
    Http::fake([
        '*' => Http::response('<html>SUCCESS</html>', 200),
    ]);

    $issue = Issue::factory()->create();
    $article = Article::factory()->published()->create(['issue_id' => $issue->id, 'doi' => null]);

    (new DepositArticleToCrossref($article))->handle(
        app(DoiMinter::class),
        app(CrossrefXmlBuilder::class),
        app(CrossrefClient::class),
    );

    $deposit = CrossrefDeposit::where('article_id', $article->id)->sole();
    expect($deposit->status)->toBe(CrossrefDeposit::STATUS_ACCEPTED);
    expect($deposit->http_status)->toBe(200);
    expect($article->fresh()->doi_registered_at)->not->toBeNull();
    expect($article->fresh()->doi)->not->toBeNull();
});

test('failed deposit records failed status and throws', function () {
    Http::fake([
        '*' => Http::response('bad', 500),
    ]);

    $article = Article::factory()->published()->create(['doi' => null]);

    expect(fn () => (new DepositArticleToCrossref($article))->handle(
        app(DoiMinter::class),
        app(CrossrefXmlBuilder::class),
        app(CrossrefClient::class),
    ))->toThrow(RuntimeException::class);

    $deposit = CrossrefDeposit::where('article_id', $article->id)->sole();
    expect($deposit->status)->toBe(CrossrefDeposit::STATUS_FAILED);
    expect($deposit->http_status)->toBe(500);
    expect($article->fresh()->doi_registered_at)->toBeNull();
});

test('minted DOI is persisted before deposit and reused across retries', function () {
    Http::fake([
        '*' => Http::response('bad', 500),
    ]);

    $article = Article::factory()->published()->create(['doi' => null]);

    $job = new DepositArticleToCrossref($article);

    expect(fn () => $job->handle(
        app(DoiMinter::class),
        app(CrossrefXmlBuilder::class),
        app(CrossrefClient::class),
    ))->toThrow(RuntimeException::class);

    $firstDoi = $article->fresh()->doi;
    expect($firstDoi)->not->toBeNull();

    $retry = new DepositArticleToCrossref($article->fresh());
    expect(app(DoiMinter::class)->mint($retry->article))->toBe($firstDoi);

    $secondDoi = $article->fresh()->doi;
    expect($secondDoi)->toBe($firstDoi);
});

test('job fails without persisting anything when prefix is not configured', function () {
    config(['services.crossref.prefix' => null]);

    $article = Article::factory()->published()->create(['doi' => null]);

    expect(fn () => (new DepositArticleToCrossref($article))->handle(
        app(DoiMinter::class),
        app(CrossrefXmlBuilder::class),
        app(CrossrefClient::class),
    ))->toThrow(DoiPrefixNotConfiguredException::class);

    expect(CrossrefDeposit::where('article_id', $article->id)->doesntExist())->toBeTrue();
    expect($article->fresh()->doi)->toBeNull();
    expect($article->fresh()->doi_registered_at)->toBeNull();
});

test('deposit succeeds and persists DOI when the early persist fails', function () {
    Http::fake([
        '*' => Http::response('<html>SUCCESS</html>', 200),
    ]);

    $article = Article::factory()->published()->create(['doi' => null]);

    $guard = true;
    Article::saving(function (Article $model) use (&$guard) {
        if ($guard && $model->isDirty('doi') && ! $model->isDirty('doi_registered_at')) {
            $guard = false;

            throw new RuntimeException('DB outage');
        }
    });

    (new DepositArticleToCrossref($article))->handle(
        app(DoiMinter::class),
        app(CrossrefXmlBuilder::class),
        app(CrossrefClient::class),
    );

    $fresh = $article->fresh();
    expect($fresh->doi)->not->toBeNull();
    expect($fresh->doi_registered_at)->not->toBeNull();

    $deposit = CrossrefDeposit::where('article_id', $article->id)->sole();
    expect($deposit->status)->toBe(CrossrefDeposit::STATUS_ACCEPTED);
    expect($deposit->http_status)->toBe(200);
    expect($deposit->xml_payload)->toContain($fresh->doi);
});

test('retry reuses the DOI reserved in the deposit record when the article persist failed', function () {
    Http::fake([
        '*' => Http::response('bad', 500),
    ]);

    $article = Article::factory()->published()->create(['doi' => null]);

    $guard = true;
    Article::saving(function (Article $model) use (&$guard) {
        if ($guard && $model->isDirty('doi') && ! $model->isDirty('doi_registered_at')) {
            $guard = false;

            throw new RuntimeException('DB outage');
        }
    });

    expect(fn () => (new DepositArticleToCrossref($article))->handle(
        app(DoiMinter::class),
        app(CrossrefXmlBuilder::class),
        app(CrossrefClient::class),
    ))->toThrow(RuntimeException::class);

    $fresh = $article->fresh();
    expect($fresh->doi)->toBeNull();

    $firstDeposit = CrossrefDeposit::where('article_id', $article->id)->latest('id')->first();
    expect($firstDeposit->doi)->not->toBeNull();

    expect(fn () => (new DepositArticleToCrossref($fresh))->handle(
        app(DoiMinter::class),
        app(CrossrefXmlBuilder::class),
        app(CrossrefClient::class),
    ))->toThrow(RuntimeException::class);

    expect($article->fresh()->doi)->toBe($firstDeposit->doi);

    $secondDeposit = CrossrefDeposit::where('article_id', $article->id)->latest('id')->first();
    expect($secondDeposit->doi)->toBe($firstDeposit->doi);
    expect($secondDeposit->batch_id)->not->toBe($firstDeposit->batch_id);
});
