<?php

use App\Enums\ArticleStatus;
use App\Jobs\DepositArticleToCrossref;
use App\Models\Article;
use App\Models\Correction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

test('redeposit refuses to run when the prefix is not configured', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => null,
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    Article::factory()->published()->create([
        'doi_registered_at' => now(),
        'status' => ArticleStatus::Retracted,
    ]);

    $this->artisan('crossref:redeposit')
        ->expectsOutputToContain('Crossref is disabled or the prefix is not configured')
        ->assertExitCode(Command::FAILURE);

    Queue::assertNothingPushed();
});

test('redeposit refuses to run when the policy DOI is not configured', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => null,
    ]);

    Article::factory()->published()->create([
        'doi_registered_at' => now(),
        'status' => ArticleStatus::Retracted,
    ]);

    $this->artisan('crossref:redeposit')
        ->expectsOutputToContain('CROSSMARK_POLICY_DOI is not configured')
        ->assertExitCode(Command::FAILURE);

    Queue::assertNothingPushed();
});

test('redeposit dispatches retraction re-deposits for retracted articles', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    $retracted = Article::factory()->published()->create([
        'doi_registered_at' => now(),
        'status' => ArticleStatus::Retracted,
        'retracted_at' => now(),
    ]);
    Article::factory()->published()->create(['doi_registered_at' => now()]);

    $this->artisan('crossref:redeposit')->assertExitCode(Command::SUCCESS);

    Queue::assertPushed(DepositArticleToCrossref::class, 1);
    Queue::assertPushed(DepositArticleToCrossref::class, fn ($job) => $job->article->id === $retracted->id
        && $job->updateType === 'retraction');
});

test('redeposit dispatches correction re-deposits for articles with corrections', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    $eic = User::factory()->create();
    $article = Article::factory()->published()->create(['doi_registered_at' => now()]);
    Correction::create([
        'article_id' => $article->id,
        'type' => 'erratum',
        'title' => 'Correction title',
        'description' => 'Correction description',
        'published_at' => now(),
        'created_by' => $eic->id,
    ]);

    $this->artisan('crossref:redeposit')->assertExitCode(Command::SUCCESS);

    Queue::assertPushed(DepositArticleToCrossref::class, 1);
    Queue::assertPushed(DepositArticleToCrossref::class, fn ($job) => $job->article->id === $article->id
        && $job->updateType === 'correction');
});

test('redeposit skips articles whose initial DOI deposit never happened', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    Article::factory()->published()->create([
        'doi_registered_at' => null,
        'status' => ArticleStatus::Retracted,
    ]);

    $this->artisan('crossref:redeposit')
        ->expectsOutputToContain('No articles to re-deposit')
        ->assertExitCode(Command::SUCCESS);

    Queue::assertNothingPushed();
});

test('redeposit dry-run lists candidates without dispatching', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
        'services.crossref.crossmark.policy_doi' => '10.5555/crossmark-policy',
    ]);

    $article = Article::factory()->published()->create([
        'doi_registered_at' => now(),
        'status' => ArticleStatus::Retracted,
        'retracted_at' => now(),
    ]);

    $this->artisan('crossref:redeposit', ['--dry-run' => true])
        ->expectsOutputToContain("[{$article->id}]")
        ->assertExitCode(Command::SUCCESS);

    Queue::assertNothingPushed();
});
