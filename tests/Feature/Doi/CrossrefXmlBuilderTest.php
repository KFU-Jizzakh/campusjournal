<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Correction;
use App\Models\Issue;
use App\Services\Doi\CrossrefXmlBuilder;
use Illuminate\Support\Facades\DB;

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

test('builds valid Crossref XML with article metadata', function () {
    $issue = Issue::factory()->create(['volume' => 5, 'number' => 2, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Sample Title',
        'doi' => '10.12345/sample',
        'first_page' => '10',
        'last_page' => '25',
        'abstract_en' => 'An abstract.',
        'issue_id' => $issue->id,
    ]);
    $author = Author::factory()->create([
        'full_name' => 'Ivanov Ivan',
        'first_name' => 'Ivan',
        'last_name' => 'Ivanov',
        'orcid' => '0000-0001-2345-6789',
        'organization' => 'KFU',
    ]);
    $article->authors()->attach($author, ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-123');

    expect($xml)->toContain('<doi_batch_id>batch-123</doi_batch_id>');
    expect($xml)->toContain('<title>Sample Title</title>');
    expect($xml)->toContain('<doi>10.12345/sample</doi>');
    expect($xml)->toContain('<given_name>Ivan</given_name>');
    expect($xml)->toContain('<surname>Ivanov</surname>');
    expect($xml)->toContain('<first_page>10</first_page>');
    expect($xml)->toContain('<last_page>25</last_page>');
    expect($xml)->toContain('https://orcid.org/0000-0001-2345-6789');
    expect($xml)->toContain('<volume>5</volume>');

    $dom = new DOMDocument;
    expect(@$dom->loadXML($xml))->toBeTrue();
});

test('includes crossmark base metadata without update type', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/base',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-ck');

    expect($xml)->toContain('<crossmark>');
    expect($xml)->toContain('<crossmark_version>1</crossmark_version>');
    expect($xml)->toContain('<crossmark_policy>10.5555/crossmark-policy</crossmark_policy>');
    expect($xml)->toContain('<crossmark_domains>');
    expect($xml)->toContain('<crossmark_domain>');
    expect($xml)->toContain('<domain>journal.example</domain>');
    expect($xml)->not()->toContain('<updates>');
    expect($xml)->not()->toContain('<update type="retraction"');
    expect($xml)->not()->toContain('<update type="correction"');

    expect(strpos($xml, '</head>'))->toBeLessThan(strpos($xml, '<crossmark>'));
    expect(strpos($xml, '<crossmark>'))->toBeLessThan(strpos($xml, '<doi_data>'));
});

test('includes updates block when update type is provided', function (string $updateType) {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/up',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    if ($updateType === 'retraction') {
        $article->forceFill(['retracted_at' => '2026-07-01 12:00:00'])->save();
    }

    if ($updateType === 'correction') {
        Correction::create([
            'article_id' => $article->id,
            'type' => 'corrigendum',
            'title' => 'Test Correction',
            'description' => 'A correction.',
            'published_at' => '2026-07-02 09:30:00',
            'created_by' => null,
        ]);
    }

    $xml = app(CrossrefXmlBuilder::class)->build($article->fresh(), 'batch-up', $updateType);

    $expectedUpdate = $updateType === 'retraction'
        ? '<update type="retraction" date="2026-07-01">10.12345/up</update>'
        : '<update type="corrigendum" date="2026-07-02">10.12345/up</update>';

    expect($xml)->toContain('<updates>');
    expect($xml)->toContain($expectedUpdate);
    expect(strpos($xml, '<updates>'))->toBeLessThan(strpos($xml, '<doi_data>'));
})->with(['retraction', 'correction']);

test('retraction update falls back to today when retracted_at is missing', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/fallback',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-up', 'retraction');

    expect($xml)->toContain('<update type="retraction" date="'.now()->toDateString().'">10.12345/fallback</update>');
});

test('updates block is omitted when the article has no DOI', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => null,
        'retracted_at' => '2026-08-01 12:00:00',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-up', 'retraction');

    expect($xml)->not()->toContain('<updates>');
    expect($xml)->not()->toMatch('/<update\b[^>]*><\/update>/');
});

test('retraction deposit preserves existing corrections in the updates block', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/corr+retract',
        'retracted_at' => '2026-08-01 12:00:00',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    Correction::create([
        'article_id' => $article->id,
        'type' => 'erratum',
        'title' => 'Earlier correction',
        'description' => 'One.',
        'published_at' => '2026-07-02 09:30:00',
        'created_by' => null,
    ]);

    $xml = app(CrossrefXmlBuilder::class)->build($article->fresh(), 'batch-corr-retract', 'retraction');

    expect($xml)->toContain('<update type="erratum" date="2026-07-02">10.12345/corr+retract</update>');
    expect($xml)->toContain('<update type="retraction" date="2026-08-01">10.12345/corr+retract</update>');
    expect(strpos($xml, '<update type='))
        ->toBeLessThan(strpos($xml, '<update type="retraction"'));
});

test('multiple corrections render oldest first with the retraction update trailing', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/ordered',
        'retracted_at' => '2026-08-01 12:00:00',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    Correction::create([
        'article_id' => $article->id,
        'type' => 'erratum',
        'title' => 'Later correction',
        'description' => 'One.',
        'published_at' => '2026-07-10 09:30:00',
        'created_by' => null,
    ]);
    Correction::create([
        'article_id' => $article->id,
        'type' => 'corrigendum',
        'title' => 'Earlier correction',
        'description' => 'Two.',
        'published_at' => '2026-07-02 09:30:00',
        'created_by' => null,
    ]);

    $xml = app(CrossrefXmlBuilder::class)->build($article->fresh(), 'batch-ordered', 'retraction');

    $earlier = strpos($xml, '<update type="corrigendum" date="2026-07-02">10.12345/ordered</update>');
    $later = strpos($xml, '<update type="erratum" date="2026-07-10">10.12345/ordered</update>');
    $retraction = strpos($xml, '<update type="retraction" date="2026-08-01">10.12345/ordered</update>');

    expect($earlier)->toBeGreaterThan(0);
    expect($later)->toBeGreaterThan(0);
    expect($retraction)->toBeGreaterThan(0);
    expect($earlier)->toBeLessThan($later);
    expect($later)->toBeLessThan($retraction);
});

test('update content uses the explicit doi when the article has none stored', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => null,
        'retracted_at' => '2026-08-01 12:00:00',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-up', 'retraction', '10.12345/minted');

    expect($xml)->toContain('<update type="retraction" date="2026-08-01">10.12345/minted</update>');
    expect($xml)->toContain('<doi>10.12345/minted</doi>');
    expect($xml)->not()->toContain('<update type="retraction"></update>');
});

test('omits crossmark_domains when only a dot-less host is configured', function () {
    config([
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
        'services.crossref.crossmark.domains' => [],
    ]);

    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/nodomain',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-nodomain');

    expect($xml)->toContain('<crossmark>');
    expect($xml)->not()->toContain('<crossmark_domains>');
});

test('correction update renders one update per correction', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/multi',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    Correction::create([
        'article_id' => $article->id,
        'type' => 'erratum',
        'title' => 'First',
        'description' => 'One.',
        'published_at' => '2026-07-02 09:30:00',
        'created_by' => null,
    ]);
    Correction::create([
        'article_id' => $article->id,
        'type' => 'corrigendum',
        'title' => 'Second',
        'description' => 'Two.',
        'published_at' => '2026-08-03 10:00:00',
        'created_by' => null,
    ]);

    $xml = app(CrossrefXmlBuilder::class)->build($article->fresh(), 'batch-multi', 'correction');

    expect($xml)->toContain('<update type="erratum" date="2026-07-02">10.12345/multi</update>');
    expect($xml)->toContain('<update type="corrigendum" date="2026-08-03">10.12345/multi</update>');
});

test('correction update falls back to today when published_at is missing', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/nodate',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $correction = Correction::create([
        'article_id' => $article->id,
        'type' => 'corrigendum',
        'title' => 'Undated',
        'description' => 'One.',
        'published_at' => '2026-07-02 09:30:00',
        'created_by' => null,
    ]);

    DB::statement('ALTER TABLE corrections ALTER COLUMN published_at DROP NOT NULL');
    DB::table('corrections')->where('id', $correction->id)->update(['published_at' => null]);

    $xml = app(CrossrefXmlBuilder::class)->build($article->fresh(), 'batch-nodate', 'correction');

    expect($xml)->toContain('<update type="corrigendum" date="'.now()->toDateString().'">10.12345/nodate</update>');
});

test('corrections with the same published_at render by id', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/same',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    Correction::create([
        'article_id' => $article->id,
        'type' => 'corrigendum',
        'title' => 'First',
        'description' => 'One.',
        'published_at' => '2026-07-02 09:30:00',
        'created_by' => null,
    ]);
    $rectification = Correction::create([
        'article_id' => $article->id,
        'type' => 'erratum',
        'title' => 'Second',
        'description' => 'Two.',
        'published_at' => '2026-07-02 09:30:00',
        'created_by' => null,
    ]);

    $xml = app(CrossrefXmlBuilder::class)->build($article->fresh(), 'batch-same', 'correction');

    expect($rectification->id)->toBeGreaterThan(Correction::first()->id);
    expect(strpos($xml, '<update type="corrigendum"'))
        ->toBeLessThan(strpos($xml, '<update type="erratum"'));
});

test('does not include updates for correction when no corrections exist', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/nocorr',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-no-correction', 'correction');

    expect($xml)->toContain('<crossmark>');
    expect($xml)->not()->toContain('<updates>');
    expect($xml)->not()->toContain('<update type="correction"');
});

test('omits the crossmark block entirely when the policy doi is not configured', function () {
    config(['services.crossref.crossmark.policy_doi' => null]);

    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/nocm',
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-nocm', 'retraction');

    expect($xml)->not()->toContain('<crossmark>');
    expect($xml)->toContain('<doi_data>');
});

test('funding renders inside crossmark custom_metadata before doi_data', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/fund',
        'funding' => [
            ['funder_name' => 'RSF', 'funder_identifier' => null, 'award_number' => '20-11-00001'],
            ['funder_name' => 'KFU', 'funder_identifier' => null, 'award_number' => null],
        ],
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-fund');

    expect(substr_count($xml, '<fr:program name="fundref"'))->toBe(1);
    expect($xml)->toContain('<custom_metadata>');
    expect($xml)->toContain('<fr:assertion name="funder_name">RSF</fr:assertion>');
    expect($xml)->toContain('<fr:assertion name="award_number">20-11-00001</fr:assertion>');
    expect($xml)->toContain('<fr:assertion name="funder_name">KFU</fr:assertion>');
    expect(strpos($xml, '<fr:program name="fundref"'))->toBeLessThan(strpos($xml, '<doi_data>'));
    expect(strpos($xml, '<custom_metadata>'))->toBeLessThan(strpos($xml, '<doi_data>'));
});

test('funding renders as a direct child before doi_data when crossmark is absent', function () {
    config(['services.crossref.crossmark.policy_doi' => null]);

    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => '10.12345/fund2',
        'funding' => [
            ['funder_name' => 'RSF', 'funder_identifier' => null, 'award_number' => '20-11-00001'],
        ],
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-fund2');

    expect($xml)->not()->toContain('<crossmark>');
    expect($xml)->not()->toContain('<custom_metadata>');
    expect(substr_count($xml, '<fr:program name="fundref"'))->toBe(1);
    expect(strpos($xml, '<fr:program name="fundref"'))->toBeLessThan(strpos($xml, '<doi_data>'));
});

test('uses explicit doi when the article has none stored', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => null,
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-doi', null, '10.12345/abc23456');

    expect($xml)->toContain('<doi>10.12345/abc23456</doi>');
});

test('does not include doi_data when neither stored nor explicit doi exists', function () {
    $issue = Issue::factory()->create(['volume' => 1, 'number' => 1, 'year' => 2026]);
    $article = Article::factory()->published()->create([
        'title' => 'Test',
        'doi' => null,
        'issue_id' => $issue->id,
    ]);
    $article->authors()->attach(Author::factory()->create(), ['order' => 0]);

    $xml = app(CrossrefXmlBuilder::class)->build($article, 'batch-none');

    expect($xml)->not->toContain('<doi_data>');
});
