<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;

test('exports JATS XML for published article', function () {
    $article = Article::factory()->published()->create(['title' => 'Exported']);

    $response = $this->get("/articles/{$article->id}/jats.xml");

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/xml; charset=utf-8');
    expect($response->headers->get('Content-Disposition'))->toContain("filename=article-{$article->id}.xml");
    expect($response->getContent())->toContain('<article-title>Exported</article-title>');
});

test('JATS export uses the per-article contact snapshot', function () {
    $article = Article::factory()->published()->create(['title' => 'Snapshot']);

    $author = Author::create([
        'full_name' => 'Иванов Иван Иванович',
        'email' => 'shared@example.test',
        'phone' => '+7 (900) 333-33-33',
    ]);

    $article->authors()->attach($author->id, [
        'order' => 1,
        'email' => 'snapshot@example.test',
        'phone' => '+7 (900) 111-11-11',
        'country' => 'Россия',
        'city' => 'Казань',
    ]);

    $response = $this->get("/articles/{$article->id}/jats.xml");

    $response->assertOk();
    expect($response->getContent())
        ->toContain('<email>snapshot@example.test</email>')
        ->not->toContain('shared@example.test');
});

test('unpublished article returns 404', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    $this->get("/articles/{$article->id}/jats.xml")->assertNotFound();
});
