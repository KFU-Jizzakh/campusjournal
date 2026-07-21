<?php

use App\Jobs\DepositArticleToCrossref;
use App\Models\Article;
use App\Models\Issue;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('publishing dispatches deposit job when crossref enabled', function () {
    Queue::fake();
    config(['services.crossref.enabled' => true]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $issue = Issue::factory()->create();
    $article = Article::factory()->approved()->create();

    $this->actingAs($eic)
        ->post(route('editorial.publish', $article), ['issue_id' => $issue->id])
        ->assertRedirect();

    Queue::assertPushed(DepositArticleToCrossref::class);
});

test('publishing does not dispatch when crossref disabled', function () {
    Queue::fake();
    config(['services.crossref.enabled' => false]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $issue = Issue::factory()->create();
    $article = Article::factory()->approved()->create();

    $this->actingAs($eic)
        ->post(route('editorial.publish', $article), ['issue_id' => $issue->id])
        ->assertRedirect();

    Queue::assertNotPushed(DepositArticleToCrossref::class);
});
