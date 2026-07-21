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
