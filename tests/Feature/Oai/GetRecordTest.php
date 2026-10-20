<?php

use App\Enums\ArticleStatus;
use App\Models\Article;

beforeEach(fn () => config(['oai.repository_id' => 'test.example']));

test('returns single record by identifier', function () {
    $article = Article::factory()->published()->create(['title' => 'One']);
    $id = 'oai:test.example:article:'.$article->id;

    $body = $this->get('/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier='.urlencode($id))->getContent();

    expect($body)->toContain('<dc:title>One</dc:title>');
});

test('unknown identifier returns idDoesNotExist', function () {
    $body = $this->get('/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier=oai:test.example:article:99999')->getContent();

    expect($body)->toContain('code="idDoesNotExist"');
});

test('bad prefix returns cannotDisseminateFormat', function () {
    $article = Article::factory()->published()->create();
    $id = 'oai:test.example:article:'.$article->id;

    $body = $this->get('/oai?verb=GetRecord&metadataPrefix=bogus&identifier='.urlencode($id))->getContent();

    expect($body)->toContain('code="cannotDisseminateFormat"');
});

test('draft article not accessible', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);
    $id = 'oai:test.example:article:'.$article->id;

    $body = $this->get('/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier='.urlencode($id))->getContent();

    expect($body)->toContain('code="idDoesNotExist"');
});

test('soft-deleted unpublished article returns idDoesNotExist', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Rejected]);
    $article->delete();
    $id = 'oai:test.example:article:'.$article->id;

    $body = $this->get('/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier='.urlencode($id))->getContent();

    expect($body)->toContain('code="idDoesNotExist"');
});

test('soft-deleted published article serves tombstone', function () {
    $article = Article::factory()->published()->create();
    $article->delete();
    $id = 'oai:test.example:article:'.$article->id;

    $body = $this->get('/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier='.urlencode($id))->getContent();

    expect($body)->toContain('status="deleted"');
});

test('retracted article remains accessible', function () {
    $article = Article::factory()->published()->create(['title' => 'Retracted Study']);
    $article->update(['status' => ArticleStatus::Retracted]);
    $id = 'oai:test.example:article:'.$article->id;

    $body = $this->get('/oai?verb=GetRecord&metadataPrefix=oai_dc&identifier='.urlencode($id))->getContent();

    expect($body)->toContain('<dc:title>Retracted Study</dc:title>');
});
