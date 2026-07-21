<?php

use App\Models\Article;

beforeEach(fn () => config(['oai.repository_id' => 'test.example']));

test('ListMetadataFormats includes nlm', function () {
    $body = $this->get('/oai?verb=ListMetadataFormats')->getContent();
    expect($body)->toContain('<metadataPrefix>nlm</metadataPrefix>');
});

test('GetRecord with nlm prefix returns JATS article', function () {
    $article = Article::factory()->published()->create(['title' => 'Harvested']);
    $id = 'oai:test.example:article:'.$article->id;

    $body = $this->get('/oai?verb=GetRecord&metadataPrefix=nlm&identifier='.urlencode($id))->getContent();

    expect($body)->toContain('<article ');
    expect($body)->toContain('<article-title>Harvested</article-title>');

    $dom = new DOMDocument;
    expect(@$dom->loadXML($body))->toBeTrue();
});
