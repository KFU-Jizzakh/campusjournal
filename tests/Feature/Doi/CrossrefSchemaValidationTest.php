<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Correction;
use App\Models\Issue;
use App\Services\Doi\CrossrefXmlBuilder;

beforeEach(function () {
    config([
        'services.crossref.depositor_name' => 'Test Depositor',
        'services.crossref.depositor_email' => 'dep@example.com',
        'services.crossref.registrant' => 'Test Registrant',
        'services.crossref.crossmark' => [
            'policy_doi' => '10.5555/crossmark-policy',
            'domains' => ['journal.example'],
        ],
    ]);
});

function assertCrossrefSchemaValid(string $xml): void
{
    $schemasDir = __DIR__.'/../../fixtures/schemas';

    libxml_set_external_entity_loader(function (?string $public, ?string $system, array $context) use ($schemasDir) {
        if ($system === null) {
            return null;
        }
        if (str_starts_with($system, 'http://www.w3.org/Math/XMLSchema/mathml3/')) {
            $system = $schemasDir.'/'.basename($system);
        }
        if (! is_file($system)) {
            return null;
        }

        return $system;
    });

    try {
        $dom = new DOMDocument;
        expect($dom->loadXML($xml))->toBeTrue();

        $errors = [];
        libxml_use_internal_errors(true);
        $valid = $dom->schemaValidate($schemasDir.'/crossref5.3.1.xsd');
        foreach (libxml_get_errors() as $error) {
            $errors[] = trim($error->message);
        }
        libxml_clear_errors();

        expect($valid)->toBeTrue(implode("\n", $errors));
        libxml_use_internal_errors(false);
    } finally {
        libxml_set_external_entity_loader(null);
    }
}

test('full retraction deposit validates against the Crossref 5.3.1 schema', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026, 'published_at' => '2026-01-15 10:00:00']);
    $article = Article::factory()->published()->create([
        'title' => 'Schema Test',
        'doi' => '10.12345/schema',
        'abstract_en' => 'An abstract.',
        'first_page' => '10',
        'last_page' => '15',
        'retracted_at' => '2026-08-01 12:00:00',
        'funding' => [
            ['funder_name' => 'RSF', 'funder_identifier' => null, 'award_number' => '20-11-00001'],
        ],
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(['organization' => 'KFU']), ['order' => 0]);

    Correction::create([
        'article_id' => $article->id,
        'type' => 'erratum',
        'title' => 'Erratum',
        'description' => 'One.',
        'published_at' => '2026-05-02 09:30:00',
        'created_by' => null,
    ]);
    Correction::create([
        'article_id' => $article->id,
        'type' => 'corrigendum',
        'title' => 'Corrigendum',
        'description' => 'Two.',
        'published_at' => '2026-06-03 10:00:00',
        'created_by' => null,
    ]);

    $xml = app(CrossrefXmlBuilder::class)->build($article->fresh(), 'batch-schema', 'retraction');

    assertCrossrefSchemaValid($xml);
});

test('correction-only deposit validates against the Crossref 5.3.1 schema', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026, 'published_at' => '2026-01-15 10:00:00']);
    $article = Article::factory()->published()->create([
        'title' => 'Correction Schema',
        'doi' => '10.12345/corr-schema',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    Correction::create([
        'article_id' => $article->id,
        'type' => 'expression_of_concern',
        'title' => 'EoC',
        'description' => 'Concern.',
        'published_at' => '2026-07-10 09:00:00',
        'created_by' => null,
    ]);

    $xml = app(CrossrefXmlBuilder::class)->build($article->fresh(), 'batch-corr-schema', 'correction');

    assertCrossrefSchemaValid($xml);
});

test('deposit without crossmark validates against the Crossref 5.3.1 schema', function () {
    config(['services.crossref.crossmark.policy_doi' => null]);

    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026, 'published_at' => '2026-01-15 10:00:00']);
    $article = Article::factory()->published()->create([
        'title' => 'No Crossmark',
        'doi' => '10.12345/nocm',
        'funding' => [
            ['funder_name' => 'RSF', 'funder_identifier' => null, 'award_number' => '20-11-00001'],
        ],
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-nocm-schema');

    assertCrossrefSchemaValid($xml);
});
