<?php

use App\Models\Article;

beforeEach(fn () => config(['oai.repository_id' => 'test.example']));

test('lists all three formats', function () {
    $body = $this->get('/oai?verb=ListMetadataFormats')->getContent();

    expect($body)->toContain('<metadataPrefix>oai_dc</metadataPrefix>');
    expect($body)->toContain('<metadataPrefix>oai_doaj</metadataPrefix>');
    expect($body)->toContain('<metadataPrefix>crossref</metadataPrefix>');
});

test('per-identifier ListMetadataFormats works', function () {
    $article = Article::factory()->published()->create();
    $id = 'oai:test.example:article:'.$article->id;

    $body = $this->get('/oai?verb=ListMetadataFormats&identifier='.urlencode($id))->getContent();

    expect($body)->toContain('<metadataPrefix>oai_dc</metadataPrefix>');
});

test('unknown identifier yields idDoesNotExist', function () {
    $body = $this->get('/oai?verb=ListMetadataFormats&identifier=oai:test.example:article:9999')->getContent();

    expect($body)->toContain('code="idDoesNotExist"');
});
