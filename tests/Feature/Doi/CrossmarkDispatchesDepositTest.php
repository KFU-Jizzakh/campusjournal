<?php

use App\Jobs\DepositArticleToCrossref;
use App\Models\Article;
use App\Models\Correction;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('retraction dispatches crossmark deposit when crossref enabled and prefix configured', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create();

    $this->actingAs($eic)
        ->post(route('editorial.retract', $article), ['reason' => 'Plagiarism'])
        ->assertRedirect();

    Queue::assertPushed(DepositArticleToCrossref::class, fn ($job) => $job->updateType === 'retraction');
});

test('retraction does not dispatch crossmark deposit without a configured prefix', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => null,
    ]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create();

    $this->actingAs($eic)
        ->post(route('editorial.retract', $article), ['reason' => 'Plagiarism'])
        ->assertRedirect();

    Queue::assertNotPushed(DepositArticleToCrossref::class);
});

test('retraction does not dispatch crossmark deposit without a policy DOI and warns the operator', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => null,
    ]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create();

    $this->actingAs($eic)
        ->post(route('editorial.retract', $article), ['reason' => 'Plagiarism'])
        ->assertRedirect();

    Queue::assertNotPushed(DepositArticleToCrossref::class);
    $this->assertStringContainsString('не настроен CROSSMARK_POLICY_DOI.', (string) session('warning'));
});

test('retraction warning distinguishes an invalid policy DOI from a missing one', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => null,
        'services.crossref.crossmark.policy_doi_misconfigured' => true,
    ]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create();

    $this->actingAs($eic)
        ->post(route('editorial.retract', $article), ['reason' => 'Plagiarism'])
        ->assertRedirect();

    Queue::assertNotPushed(DepositArticleToCrossref::class);
    $this->assertStringContainsString('не является валидным Crossref DOI', (string) session('warning'));
});

test('adding a correction dispatches crossmark deposit when prefix configured', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create();

    $this->actingAs($eic)
        ->post(route('editorial.corrections.store', $article), [
            'type' => 'corrigendum',
            'title' => 'Correction title',
            'description' => 'Correction description',
            'published_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    Queue::assertPushed(DepositArticleToCrossref::class, fn ($job) => $job->updateType === 'correction');
});

test('adding a correction does not dispatch crossmark deposit without a configured prefix', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => null,
    ]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create();

    $this->actingAs($eic)
        ->post(route('editorial.corrections.store', $article), [
            'type' => 'erratum',
            'title' => 'Correction title',
            'description' => 'Correction description',
            'published_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    Queue::assertNotPushed(DepositArticleToCrossref::class);
});

test('adding a correction does not dispatch crossmark deposit without a policy DOI and warns the operator', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => null,
    ]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create();

    $this->actingAs($eic)
        ->post(route('editorial.corrections.store', $article), [
            'type' => 'erratum',
            'title' => 'Correction title',
            'description' => 'Correction description',
            'published_at' => now()->toDateString(),
        ])
        ->assertRedirect();

    Queue::assertNotPushed(DepositArticleToCrossref::class);
    $this->assertStringContainsString('не настроен CROSSMARK_POLICY_DOI.', (string) session('warning'));
});

test('deleting a correction dispatches crossmark deposit when prefix configured', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create();
    $correction = Correction::create([
        'article_id' => $article->id,
        'type' => 'erratum',
        'title' => 'Correction title',
        'description' => 'Correction description',
        'published_at' => now(),
        'created_by' => $eic->id,
    ]);

    $this->actingAs($eic)
        ->delete(route('editorial.corrections.destroy', [$article, $correction]))
        ->assertRedirect();

    Queue::assertPushed(DepositArticleToCrossref::class, fn ($job) => $job->updateType === 'correction');
});
