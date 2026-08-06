<?php

use App\Jobs\DepositArticleToCrossref;
use App\Models\Article;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

test('backfill refuses to run when prefix is not configured', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => null,
    ]);

    Article::factory()->published()->create(['doi' => null]);

    $this->artisan('crossref:backfill')
        ->expectsOutputToContain('Crossref is disabled or the prefix is not configured')
        ->assertExitCode(Command::FAILURE);

    Queue::assertNothingPushed();
});

test('backfill refuses to run when crossref is disabled', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => false,
        'services.crossref.prefix' => '10.12345',
    ]);

    Article::factory()->published()->create(['doi' => null]);

    $this->artisan('crossref:backfill')
        ->expectsOutputToContain('Crossref is disabled or the prefix is not configured')
        ->assertExitCode(Command::FAILURE);

    Queue::assertNothingPushed();
});

test('backfill dispatches deposit jobs when crossref enabled and prefix configured', function () {
    Queue::fake();
    config([
        'services.crossref.enabled' => true,
        'services.crossref.prefix' => '10.12345',
    ]);

    $articles = Article::factory()->count(2)->published()->create(['doi' => null]);

    $this->artisan('crossref:backfill')->assertExitCode(Command::SUCCESS);

    Queue::assertPushed(DepositArticleToCrossref::class, 2);
});
