<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exports bibtex for a published article', function () {
    $issue = Issue::factory()->create(['volume' => 2, 'number' => 3, 'year' => 2025]);
    $article = Article::factory()->published()->create([
        'title' => 'Test & Title with #special chars',
        'doi' => '10.1234/test',
        'issue_id' => $issue->id,
    ]);
    $author = Author::factory()->create(['full_name' => 'John Doe']);
    $article->authors()->attach($author);

    $response = $this->get("/articles/{$article->id}/export/bibtex");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/x-bibtex; charset=utf-8');
    $content = $response->getContent();
    expect($content)->toContain('@article{gcru'.$article->id);
    expect($content)->toContain('author    = {John Doe}');
    expect($content)->toContain('Test \\& Title with \\#special chars');
    expect($content)->toContain('doi       = {10.1234/test}');
    expect($content)->toContain('volume    = {2}');
});

it('exports ris for a published article', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 2, 'year' => 2025]);
    $article = Article::factory()->published()->create([
        'title' => 'RIS Test Article',
        'doi' => '10.5678/ris',
        'issue_id' => $issue->id,
    ]);
    $author = Author::factory()->create(['full_name' => 'Jane Smith']);
    $article->authors()->attach($author);

    $response = $this->get("/articles/{$article->id}/export/ris");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/x-research-info-systems; charset=utf-8');
    $content = $response->getContent();
    expect($content)->toContain('TY  - JOUR');
    expect($content)->toContain('AU  - Jane Smith');
    expect($content)->toContain('PY  - 2025///');
    expect($content)->toContain('DO  - 10.5678/ris');
    expect($content)->toContain('ER  - ');
});

it('returns 404 for non-published article export', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    $this->get("/articles/{$article->id}/export/bibtex")->assertNotFound();
    $this->get("/articles/{$article->id}/export/ris")->assertNotFound();
});
