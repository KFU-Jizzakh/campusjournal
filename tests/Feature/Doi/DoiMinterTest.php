<?php

use App\Exceptions\DoiPrefixNotConfiguredException;
use App\Models\Article;
use App\Services\Doi\DoiMinter;

beforeEach(function () {
    config([
        'services.crossref.prefix' => '10.12345',
        'services.crossref.doi_suffix_length' => 8,
    ]);
});

test('mints full DOI with opaque random suffix when empty', function () {
    $article = Article::factory()->create(['doi' => null]);

    $doi = app(DoiMinter::class)->mint($article);

    expect($doi)->toMatch('/^10\.12345\/[a-z2-9]{8}$/');
});

test('suffix contains no ambiguous characters and no readable metadata', function () {
    $minter = app(DoiMinter::class);

    $suffix = $minter->suffix();

    expect(strlen($suffix))->toBe(8);
    expect($suffix)->toMatch('/^[abcdefghjkmnpqrstuvwxyz23456789]+$/');
    expect($suffix)->not->toMatch('/[0o1il]/');
});

test('consecutive mints produce different suffixes', function () {
    $article = Article::factory()->create(['doi' => null]);
    $minter = app(DoiMinter::class);

    expect($minter->mint($article))->not->toBe($minter->mint($article->fresh()));
});

test('respects configured suffix length', function () {
    config(['services.crossref.doi_suffix_length' => 12]);

    expect(strlen(app(DoiMinter::class)->suffix()))->toBe(12);
});

test('returns existing DOI without modification', function () {
    $article = Article::factory()->create(['doi' => '10.99999/preset']);

    $doi = app(DoiMinter::class)->mint($article);

    expect($doi)->toBe('10.99999/preset');
});

test('is configured only when a prefix is set', function () {
    $minter = app(DoiMinter::class);

    config(['services.crossref.prefix' => '10.12345']);
    expect($minter->isConfigured())->toBeTrue();

    config(['services.crossref.prefix' => null]);
    expect($minter->isConfigured())->toBeFalse();
});

test('is ready only when crossref enabled and prefix configured', function () {
    $minter = app(DoiMinter::class);

    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
    ]);
    expect($minter->isReady())->toBeTrue();

    config(['services.crossref.enabled' => false]);
    expect($minter->isReady())->toBeFalse();

    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => null,
    ]);
    expect($minter->isReady())->toBeFalse();
});

test('mint throws when prefix is not configured', function () {
    config(['services.crossref.prefix' => null]);
    $article = Article::factory()->create(['doi' => null]);

    expect(fn () => app(DoiMinter::class)->mint($article))
        ->toThrow(DoiPrefixNotConfiguredException::class);
});

test('mint returns existing DOI even without a prefix', function () {
    config(['services.crossref.prefix' => null]);
    $article = Article::factory()->create(['doi' => '10.99999/preset']);

    expect(app(DoiMinter::class)->mint($article))->toBe('10.99999/preset');
});
