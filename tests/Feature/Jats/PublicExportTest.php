<?php

use App\Enums\ArticleStatus;
use App\Models\Article;

test('exports JATS XML for published article', function () {
    $article = Article::factory()->published()->create(['title' => 'Exported']);

    $response = $this->get("/articles/{$article->id}/jats.xml");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
    expect($response->headers->get('Content-Disposition'))->toContain("filename=article-{$article->id}.xml");
    expect($response->getContent())->toContain('<article-title>Exported</article-title>');
});

test('unpublished article returns 404', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    $this->get("/articles/{$article->id}/jats.xml")->assertNotFound();
});
