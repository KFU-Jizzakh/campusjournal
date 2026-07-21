<?php

use App\Models\Article;

beforeEach(function () {
    config([
        'oai.repository_name' => 'Test Journal',
        'oai.repository_id' => 'test.example',
        'oai.admin_email' => 'admin@test.example',
    ]);
});

test('identify returns a valid envelope', function () {
    Article::factory()->published()->create(['published_at' => '2025-01-15 10:00:00']);

    $response = $this->get('/oai?verb=Identify');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    $body = $response->getContent();

    expect($body)->toContain('<repositoryName>Test Journal</repositoryName>');
    expect($body)->toContain('<protocolVersion>2.0</protocolVersion>');
    expect($body)->toContain('<adminEmail>admin@test.example</adminEmail>');
    expect($body)->toContain('<deletedRecord>persistent</deletedRecord>');
    expect($body)->toContain('<granularity>YYYY-MM-DDThh:mm:ssZ</granularity>');
    expect($body)->toContain('<repositoryIdentifier>test.example</repositoryIdentifier>');

    $dom = new DOMDocument;
    expect(@$dom->loadXML($body))->toBeTrue();
});
