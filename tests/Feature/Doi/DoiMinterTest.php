<?php

use App\Models\Article;
use App\Models\Issue;
use App\Services\Doi\DoiMinter;

beforeEach(function () {
    config([
        'services.crossref.prefix' => '10.12345',
        'services.crossref.doi_pattern' => '{prefix}/kfujournal.{year}.{volume}.{article_id}',
    ]);
});

test('mints DOI from pattern when empty', function () {
    $issue = Issue::factory()->create(['year' => 2026, 'volume' => 3]);
    $article = Article::factory()->create(['doi' => null, 'issue_id' => $issue->id]);

    $doi = app(DoiMinter::class)->mint($article);

    expect($doi)->toBe("10.12345/kfujournal.2026.3.{$article->id}");
});

test('returns existing DOI without modification', function () {
    $article = Article::factory()->create(['doi' => '10.99999/preset']);

    $doi = app(DoiMinter::class)->mint($article);

    expect($doi)->toBe('10.99999/preset');
});

test('is idempotent', function () {
    $article = Article::factory()->create(['doi' => null]);

    $first = app(DoiMinter::class)->mint($article);
    $second = app(DoiMinter::class)->mint($article->fresh());

    expect($first)->toBe($second);
});
