<?php

use App\Models\Article;

beforeEach(fn () => config(['oai.repository_id' => 'test.example']));

test('ListIdentifiers returns only headers for published articles', function () {
    Article::factory()->published()->create(['title' => 'Pub']);
    Article::factory()->create(['status' => 'draft']);

    $body = $this->get('/oai?verb=ListIdentifiers&metadataPrefix=oai_dc')->getContent();

    expect($body)->toContain('<header');
    expect($body)->toContain('<identifier>oai:test.example:article:');
    expect($body)->not->toContain('<metadata>');
});

test('ListIdentifiers omits soft-deleted unpublished articles', function () {
    $draft = Article::factory()->create(['status' => 'draft', 'title' => 'Leaked Draft']);
    $draft->delete();

    $body = $this->get('/oai?verb=ListIdentifiers&metadataPrefix=oai_dc')->getContent();

    expect($body)->not->toContain('Leaked Draft');
    expect($body)->not->toContain('status="deleted"');
});
