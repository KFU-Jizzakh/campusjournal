<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Issue;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['oai.repository_id' => 'test.example', 'oai.page_size' => 2]);
    Cache::flush();
});

test('ListRecords returns published articles with oai_dc metadata', function () {
    $cat = Category::factory()->create(['slug' => 'chem']);
    $article = Article::factory()->published()->create([
        'title' => 'Hello World',
        'category_id' => $cat->id,
        'keywords' => ['alpha', 'beta'],
        'doi' => '10.1/x',
    ]);
    Article::factory()->create(['status' => 'draft']);

    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc')->getContent();

    expect($body)->toContain('<dc:title>Hello World</dc:title>');
    expect($body)->toContain('<dc:subject>alpha</dc:subject>');
    expect($body)->toContain('<setSpec>category:chem</setSpec>');
    expect($body)->toContain('https://doi.org/10.1/x');

    $dom = new DOMDocument;
    expect(@$dom->loadXML($body))->toBeTrue();
});

test('ListRecords paginates with resumption token', function () {
    Article::factory()->count(5)->published()->create();

    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc')->getContent();

    expect($body)->toContain('<resumptionToken>');

    preg_match('#<resumptionToken>([^<]+)</resumptionToken>#', $body, $m);
    $token = $m[1];

    $body2 = $this->get('/oai?verb=ListRecords&resumptionToken='.urlencode($token))->getContent();

    expect($body2)->toContain('<record>');
});

test('ListRecords crossref format includes journal_article', function () {
    $issue = Issue::factory()->create(['volume' => 2, 'number' => 3, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Crossref Test',
        'doi' => '10.1/cr',
        'issue_id' => $issue->id,
    ]);
    $author = Author::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe', 'full_name' => 'Jane Doe']);
    $article->authors()->attach($author, ['order' => 0]);

    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=crossref')->getContent();

    expect($body)->toContain('<journal_article');
    expect($body)->toContain('<given_name>Jane</given_name>');
    expect($body)->toContain('<surname>Doe</surname>');

    $dom = new DOMDocument;
    expect(@$dom->loadXML($body))->toBeTrue();
});

test('ListRecords oai_doaj format parses as XML', function () {
    Article::factory()->published()->create(['title' => 'D Test', 'doi' => '10.1/d']);

    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_doaj')->getContent();

    expect($body)->toContain('<journalTitle>');
    expect($body)->toContain('<title>D Test</title>');

    $dom = new DOMDocument;
    expect(@$dom->loadXML($body))->toBeTrue();
});

test('ListRecords oai_doaj includes electronic ISSN from site setting', function () {
    Setting::set('journal_issn_electronic', '1234-5679');
    Article::factory()->published()->create(['title' => 'ISSN Test']);

    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_doaj')->getContent();

    expect($body)->toContain('<issn>1234-5679</issn>');
});

test('ListRecords oai_doaj omits ISSN when not configured', function () {
    Setting::set('journal_issn_electronic', '');
    Article::factory()->published()->create(['title' => 'No ISSN']);

    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_doaj')->getContent();

    expect($body)->not->toContain('<issn>');
});

test('ListRecords filters by set', function () {
    $cat = Category::factory()->create(['slug' => 'target']);
    Article::factory()->published()->create(['category_id' => $cat->id, 'title' => 'Included']);
    Article::factory()->published()->create(['title' => 'Excluded']);

    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc&set=category:target')->getContent();

    expect($body)->toContain('Included');
    expect($body)->not->toContain('Excluded');
});

test('ListRecords emits tombstone for soft-deleted published articles', function () {
    $article = Article::factory()->published()->create();
    $article->delete();

    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc')->getContent();

    expect($body)->toContain('status="deleted"');
});
