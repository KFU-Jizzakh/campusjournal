<?php

use App\Models\Article;
use App\Services\Oai\ResumptionToken;

beforeEach(fn () => config(['oai.repository_id' => 'test.example']));

test('missing verb returns badVerb', function () {
    $body = $this->get('/oai')->getContent();
    expect($body)->toContain('code="badVerb"');
});

test('unknown verb returns badVerb', function () {
    $body = $this->get('/oai?verb=Frobnicate')->getContent();
    expect($body)->toContain('code="badVerb"');
});

test('missing metadataPrefix returns badArgument', function () {
    $body = $this->get('/oai?verb=ListRecords')->getContent();
    expect($body)->toContain('code="badArgument"');
});

test('unknown set returns noRecordsMatch', function () {
    Article::factory()->published()->create();
    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc&set=category:nonexistent')->getContent();
    expect($body)->toContain('code="noRecordsMatch"');
});

test('empty result returns noRecordsMatch', function () {
    $body = $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc')->getContent();
    expect($body)->toContain('code="noRecordsMatch"');
});

test('tampered resumption token rejected', function () {
    $body = $this->get('/oai?verb=ListRecords&resumptionToken=totally-fake')->getContent();
    expect($body)->toContain('code="badResumptionToken"');
});

test('tampered signature rejected', function () {
    $valid = ResumptionToken::encode(['metadataPrefix' => 'oai_dc', 'offset' => 0]);
    [$body] = explode('.', $valid);
    $tampered = $body.'.AAAA';

    $resp = $this->get('/oai?verb=ListRecords&resumptionToken='.urlencode($tampered))->getContent();
    expect($resp)->toContain('code="badResumptionToken"');
});

test('unknown argument returns badArgument', function () {
    $body = $this->get('/oai?verb=Identify&foo=bar')->getContent();
    expect($body)->toContain('code="badArgument"');
});

test('POST works', function () {
    $body = $this->post('/oai', ['verb' => 'Identify'])->getContent();
    expect($body)->toContain('<Identify>');
});
