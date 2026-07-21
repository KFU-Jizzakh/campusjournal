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
