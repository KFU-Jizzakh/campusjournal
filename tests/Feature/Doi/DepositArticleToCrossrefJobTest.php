<?php

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
