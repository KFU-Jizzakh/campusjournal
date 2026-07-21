<?php

use App\Models\Article;
use App\Models\Reference;

test('extractDoi returns doi from text containing 10.xxxx pattern', function () {
    expect(Reference::extractDoi('Ivanov I. Foo. https://doi.org/10.1234/abc.def'))->toBe('10.1234/abc.def');
});

test('extractDoi strips trailing punctuation', function () {
    expect(Reference::extractDoi('Ref: 10.1000/xyz.123.'))->toBe('10.1000/xyz.123');
});

test('extractDoi returns null for text without doi', function () {
    expect(Reference::extractDoi('Ivanov I. Foo. 2020. No DOI here.'))->toBeNull();
});

test('doi is auto-extracted on save when raw is set', function () {
    $article = Article::factory()->create();
    $ref = Reference::create([
        'article_id' => $article->id,
        'raw' => 'Test. https://doi.org/10.9999/test.doi',
        'order' => 1,
    ]);

    expect($ref->doi)->toBe('10.9999/test.doi');
});

test('syncReferences creates reference rows from text lines', function () {
    $article = Article::factory()->create();
    $article->syncReferences([
        'Ivanov I. Foo. 2020. https://doi.org/10.1111/aaa.bbb',
        'Petrov P. Bar. 2021.',
    ]);

    expect($article->references->count())->toBe(2);
    expect($article->references->first()->raw)->toBe('Ivanov I. Foo. 2020. https://doi.org/10.1111/aaa.bbb');
    expect($article->references->first()->doi)->toBe('10.1111/aaa.bbb');
    expect($article->references->first()->order)->toBe(1);
    expect($article->references->last()->raw)->toBe('Petrov P. Bar. 2021.');
    expect($article->references->last()->doi)->toBeNull();
    expect($article->references->last()->order)->toBe(2);
});

test('syncReferences replaces existing references', function () {
    $article = Article::factory()->create();
    $article->syncReferences(['Old ref 1.', 'Old ref 2.']);
    expect($article->references->count())->toBe(2);

    $article->syncReferences(['New ref.', 'Another ref.', 'Third ref.']);
    expect($article->references->fresh()->count())->toBe(3);
    expect($article->references->first()->raw)->toBe('New ref.');
});

test('syncReferences skips empty lines', function () {
    $article = Article::factory()->create();
    $article->syncReferences(['First ref.', '', '  ', 'Second ref.']);

    expect($article->references->count())->toBe(2);
});

test('countCitations finds bracket references in body', function () {
    $article = Article::factory()->create([
        'body' => '<p>See [1] for background. Also [2] and [1] again.</p>',
    ]);
    $article->syncReferences(['Ref one.', 'Ref two.']);

    expect($article->references->first()->fresh()->cited_count)->toBe(2);
    expect($article->references->last()->fresh()->cited_count)->toBe(1);
});

test('countCitations parses range notation', function () {
    $article = Article::factory()->create([
        'body' => '<p>See [1-3] for details.</p>',
    ]);
    $article->syncReferences(['Ref 1', 'Ref 2', 'Ref 3']);

    expect($article->references->where('order', 1)->first()->fresh()->cited_count)->toBe(1);
    expect($article->references->where('order', 2)->first()->fresh()->cited_count)->toBe(1);
    expect($article->references->where('order', 3)->first()->fresh()->cited_count)->toBe(1);
});

test('countCitations parses comma-separated group', function () {
    $article = Article::factory()->create([
        'body' => '<p>As shown in [1,3,5].</p>',
    ]);
    $article->syncReferences(['R1', 'R2', 'R3', 'R4', 'R5']);

    expect($article->references->where('order', 1)->first()->fresh()->cited_count)->toBe(1);
    expect($article->references->where('order', 3)->first()->fresh()->cited_count)->toBe(1);
    expect($article->references->where('order', 5)->first()->fresh()->cited_count)->toBe(1);
    expect($article->references->where('order', 2)->first()->fresh()->cited_count)->toBe(0);
    expect($article->references->where('order', 4)->first()->fresh()->cited_count)->toBe(0);
});

test('countCitations ignores numbers without matching references', function () {
    $article = Article::factory()->create([
        'body' => '<p>See [1] and [99].</p>',
    ]);
    $article->syncReferences(['Only ref.']);

    expect($article->references->first()->fresh()->cited_count)->toBe(1);
});

test('toRis returns valid RIS record', function () {
    $article = Article::factory()->create();
    $ref = Reference::create([
        'article_id' => $article->id,
        'raw' => 'Test reference. https://doi.org/10.1234/test.ris',
        'order' => 1,
    ]);

    $ris = $ref->toRis();

    expect($ris)->toContain('TY  - JOUR');
    expect($ris)->toContain('DO  - 10.1234/test.ris');
    expect($ris)->toContain('N1  - Test reference. https://doi.org/10.1234/test.ris');
    expect($ris)->toContain('ER  -');
});

test('toBibtex returns valid BibTeX entry', function () {
    $article = Article::factory()->create();
    $ref = Reference::create([
        'article_id' => $article->id,
        'raw' => 'Test reference. https://doi.org/10.1234/test.bib',
        'order' => 1,
    ]);

    $bib = $ref->toBibtex();

    expect($bib)->toContain('@article{10_1234_test_bib,');
    expect($bib)->toContain('doi = {10.1234/test.bib}');
    expect($bib)->toContain('note = {Test reference. https://doi.org/10.1234/test.bib}');
});

test('toBibtex without DOI uses fallback key', function () {
    $article = Article::factory()->create();
    $ref = Reference::create([
        'article_id' => $article->id,
        'raw' => 'Test reference.',
        'doi' => null,
        'order' => 1,
    ]);

    $bib = $ref->toBibtex();

    expect($bib)->toContain("@article{ref{$ref->id},");
    expect($bib)->not->toContain('doi');
});
