<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Issue;
use App\Services\Doi\CrossrefXmlBuilder;

beforeEach(function () {
    config([
        'services.crossref.depositor_name' => 'Test Depositor',
        'services.crossref.depositor_email' => 'dep@example.com',
        'services.crossref.registrant' => 'Test Registrant',
    ]);
});

test('builds valid Crossref XML with article metadata', function () {
    $issue = Issue::factory()->create(['volume' => 5, 'number' => 2, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Sample Title',
        'doi' => '10.12345/sample',
        'first_page' => '10',
        'last_page' => '25',
        'abstract_en' => 'An abstract.',
        'issue_id' => $issue->id,
    ]);
    $author = Author::factory()->create([
        'full_name' => 'Ivanov Ivan',
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
        'orcid' => '0000-0001-2345-6789',
        'organization' => 'KFU',
    ]);
    $article->authors()->attach($author, ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-123');

    expect($xml)->toContain('<doi_batch_id>batch-123</doi_batch_id>');
    expect($xml)->toContain('<title>Sample Title</title>');
    expect($xml)->toContain('<doi>10.12345/sample</doi>');
    expect($xml)->toContain('<given_name>Ivan</given_name>');
    expect($xml)->toContain('<surname>Ivanov</surname>');
    expect($xml)->toContain('<first_page>10</first_page>');
    expect($xml)->toContain('<last_page>25</last_page>');
    expect($xml)->toContain('https://orcid.org/0000-0001-2345-6789');
    expect($xml)->toContain('<volume>5</volume>');

    $dom = new DOMDocument;
    expect(@$dom->loadXML($xml))->toBeTrue();
});
