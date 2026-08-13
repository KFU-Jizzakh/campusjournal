<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Author;
use App\Models\Issue;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

it('renders google scholar meta tags on article page', function () {
    $issue = Issue::factory()->create([
        'status' => 'published',
        'volume' => '12',
        'number' => '3',
    ]);
    $article = Article::factory()->published()->create([
        'issue_id' => $issue->id,
        'title' => 'Test Article Title',
        'first_page' => '10',
        'last_page' => '20',
        'doi' => '10.1234/example',
        'pdf_path' => 'articles/test.pdf',
        'keywords' => ['foo', 'bar'],
    ]);
    $author = Author::factory()->create(['full_name' => 'Ivanov Ivan Petrovich']);
    $article->authors()->attach($author->id, ['order' => 1]);

    Setting::set('journal_issn_print', '1234-5671');
    Setting::set('journal_issn_electronic', '1234-5679');

    $response = $this->get(route('articles.show', $article));

    $response
        ->assertOk()
        ->assertSee('<meta name="citation_title" content="Test Article Title">', false)
        ->assertSee('<meta name="citation_author" content="Ivanov Ivan Petrovich">', false)
        ->assertSee('<meta name="citation_journal_title" content="'.config('app.name').'">', false)
        ->assertSee('<meta name="citation_volume" content="12">', false)
        ->assertSee('<meta name="citation_issue" content="3">', false)
        ->assertSee('<meta name="citation_firstpage" content="10">', false)
        ->assertSee('<meta name="citation_lastpage" content="20">', false)
        ->assertSee('<meta name="citation_publication_date" content="'.$article->published_at->format('Y/m/d').'">', false)
        ->assertSee('<meta name="citation_doi" content="10.1234/example">', false)
        ->assertSee('<meta name="citation_pdf_url" content="'.route('articles.pdf', $article).'">', false)
        ->assertSee('<meta name="citation_abstract_html_url" content="'.route('articles.show', $article).'">', false)
        ->assertSee('<meta name="citation_language" content="ru">', false)
        ->assertSee('<meta name="citation_keywords" content="foo, bar">', false)
        ->assertSee('<meta name="citation_issn" content="1234-5679">', false)
        ->assertDontSee('<meta name="citation_issn" content="1234-5671">', false);
});

it('renders the official crossmark widget when doi and policy doi are configured', function () {
    config([
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
        'services.crossref.crossmark.domains' => ['journal.example'],
    ]);

    $article = Article::factory()->published()->create([
        'title' => 'Crossmark Article',
        'doi' => '10.1234/crossmark',
    ]);

    $response = $this->get(route('articles.show', $article));

    $response
        ->assertOk()
        ->assertSee('<meta name="dc.identifier" content="doi:10.1234/crossmark">', false)
        ->assertSee('<a data-target="crossmark" title="Crossmark">', false)
        ->assertSee('CROSSMARK_Color_horizontal.svg', false)
        ->assertSee('https://crossmark-cdn.crossref.org/widget/v2.0/widget.js', false)
        ->assertSee(route('crossmark-policy'), false);
});

it('hides the crossmark widget for articles without a doi', function () {
    config([
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    $article = Article::factory()->published()->create([
        'title' => 'No DOI Article',
        'doi' => null,
    ]);

    $response = $this->get(route('articles.show', $article));

    $response
        ->assertOk()
        ->assertDontSee('data-target="crossmark"', false)
        ->assertDontSee('crossmark-cdn.crossref.org', false)
        ->assertDontSee('dc.identifier', false);
});

it('hides the crossmark widget when the policy doi is not configured', function () {
    config([
        'services.crossref.crossmark.policy_doi' => null,
    ]);

    $article = Article::factory()->published()->create([
        'title' => 'No Policy Article',
        'doi' => '10.1234/nopolicy',
    ]);

    $response = $this->get(route('articles.show', $article));

    $response
        ->assertOk()
        ->assertDontSee('data-target="crossmark"', false)
        ->assertDontSee('crossmark-cdn.crossref.org', false);
});
