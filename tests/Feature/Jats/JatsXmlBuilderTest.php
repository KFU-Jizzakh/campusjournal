<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Issue;
use App\Models\Reference;
use App\Models\Setting;
use App\Services\Jats\JatsXmlBuilder;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

test('builds valid JATS XML with article metadata', function () {
    $issue = Issue::factory()->create(['volume' => 5, 'number' => 2, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Sample Title',
        'doi' => '10.12345/sample',
        'first_page' => '10',
        'last_page' => '25',
        'abstract_ru' => 'Русская аннотация.',
        'abstract_en' => 'English abstract.',
        'keywords' => ['alpha', 'beta'],
        'body' => '<p>Introduction text.</p>',
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

    $xml = app(JatsXmlBuilder::class)->build($article);

    expect($xml)->toContain('<article-title>Sample Title</article-title>');
    expect($xml)->toContain('<article-id pub-id-type="doi">10.12345/sample</article-id>');
    expect($xml)->toContain('<surname>Ivanov</surname>');
    expect($xml)->toContain('<given-names>Ivan</given-names>');
    expect($xml)->toContain('https://orcid.org/0000-0001-2345-6789');
    expect($xml)->toContain('<volume>5</volume>');
    expect($xml)->toContain('<fpage>10</fpage>');
    expect($xml)->toContain('<lpage>25</lpage>');
    expect($xml)->toContain('<kwd>alpha</kwd>');
    expect($xml)->toContain('<abstract xml:lang="ru">');
    expect($xml)->toContain('<trans-abstract xml:lang="en">');
    expect($xml)->toContain('<institution>KFU</institution>');

    $dom = new DOMDocument;
    expect(@$dom->loadXML($xml))->toBeTrue();
});

test('inline mode omits xml prolog', function () {
    $article = Article::factory()->published()->create();

    $xml = app(JatsXmlBuilder::class)->build($article, inline: true);

    expect($xml)->not->toContain('<?xml');
    expect($xml)->toContain('<article ');
});

test('empty body renders self-closing body tag', function () {
    $article = Article::factory()->published()->create(['body' => null]);

    $xml = app(JatsXmlBuilder::class)->build($article);

    expect($xml)->toContain('<body/>');
});

test('affiliations are deduplicated across authors', function () {
    $article = Article::factory()->published()->create();
    $a1 = Author::factory()->create(['first_name' => 'Ivan', 'last_name' => 'Ivanov', 'organization' => 'KFU']);
    $a2 = Author::factory()->create(['first_name' => 'Petr', 'last_name' => 'Petrov', 'organization' => 'KFU']);
    $article->authors()->attach($a1, ['order' => 0]);
    $article->authors()->attach($a2, ['order' => 1]);

    $xml = app(JatsXmlBuilder::class)->build($article);

    expect(substr_count($xml, '<aff id="aff1">'))->toBe(1);
    expect($xml)->not->toContain('<aff id="aff2">');
    expect(substr_count($xml, '<xref ref-type="aff" rid="aff1"/>'))->toBe(2);
});

test('article without references produces self-closing back', function () {
    $article = Article::factory()->published()->create();
    $xml = app(JatsXmlBuilder::class)->build($article);
    expect($xml)->toContain('<back/>');
});

test('references produces ref-list with mixed-citation per row', function () {
    $article = Article::factory()->published()->create();
    Reference::factory()->create(['article_id' => $article->id, 'raw' => 'Ivanov I. Foo. 2020.', 'doi' => null, 'order' => 1]);
    Reference::factory()->create(['article_id' => $article->id, 'raw' => 'Petrov P. Bar. 2021.', 'doi' => null, 'order' => 2]);

    $xml = app(JatsXmlBuilder::class)->build($article);
    expect($xml)->toContain('<back>');
    expect($xml)->toContain('<ref-list>');
    expect($xml)->toContain('<ref id="ref1"><mixed-citation>Ivanov I. Foo. 2020.</mixed-citation></ref>');
    expect($xml)->toContain('<ref id="ref2"><mixed-citation>Petrov P. Bar. 2021.</mixed-citation></ref>');
});

test('DOI in reference gets rendered as pub-id', function () {
    $article = Article::factory()->published()->create();
    Reference::factory()->create([
        'article_id' => $article->id,
        'raw' => 'Ivanov I. Foo. 2020. https://doi.org/10.1234/abc.def',
        'doi' => '10.1234/abc.def',
        'order' => 1,
    ]);

    $xml = app(JatsXmlBuilder::class)->build($article);
    expect($xml)->toContain('<pub-id pub-id-type="doi">10.1234/abc.def</pub-id>');
});

test('multiple distinct affiliations get unique ids', function () {
    $article = Article::factory()->published()->create();
    $a1 = Author::factory()->create(['first_name' => 'Ivan', 'last_name' => 'Ivanov', 'organization' => 'KFU']);
    $a2 = Author::factory()->create(['first_name' => 'Petr', 'last_name' => 'Petrov', 'organization' => 'MSU']);
    $article->authors()->attach($a1, ['order' => 0]);
    $article->authors()->attach($a2, ['order' => 1]);

    $xml = app(JatsXmlBuilder::class)->build($article);

    expect($xml)->toContain('<aff id="aff1">');
    expect($xml)->toContain('<aff id="aff2">');
    expect(substr_count($xml, '<xref ref-type="aff" rid="aff1"/>'))->toBe(1);
    expect(substr_count($xml, '<xref ref-type="aff" rid="aff2"/>'))->toBe(1);
});

test('JATS includes ISSN from site settings', function () {
    Setting::set('journal_issn_print', '1234-5671');
    Setting::set('journal_issn_electronic', '1234-5679');

    $article = Article::factory()->published()->create();
    $xml = app(JatsXmlBuilder::class)->build($article);

    expect($xml)->toContain('<issn pub-type="ppub">1234-5671</issn>');
    expect($xml)->toContain('<issn pub-type="epub">1234-5679</issn>');
});

test('JATS omits ISSN when not configured', function () {
    Setting::set('journal_issn_print', '');
    Setting::set('journal_issn_electronic', '');

    $article = Article::factory()->published()->create();
    $xml = app(JatsXmlBuilder::class)->build($article);

    expect($xml)->not->toContain('<issn ');
});

test('JATS includes only print ISSN when electronic is missing', function () {
    Setting::set('journal_issn_print', '1234-5671');
    Setting::set('journal_issn_electronic', '');

    $article = Article::factory()->published()->create();
    $xml = app(JatsXmlBuilder::class)->build($article);

    expect($xml)->toContain('<issn pub-type="ppub">1234-5671</issn>');
    expect($xml)->not->toContain('<issn pub-type="epub">');
});
