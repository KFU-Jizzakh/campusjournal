<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('increments views count on first visit', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    $article = Article::factory()->published()->create(['issue_id' => $issue->id, 'views_count' => 0]);

    $this->get(route('articles.show', $article));

    expect($article->refresh()->views_count)->toBe(1);
});

it('does not increment views count on repeated visit in same session', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    $article = Article::factory()->published()->create(['issue_id' => $issue->id, 'views_count' => 0]);

    $this->get(route('articles.show', $article));
    $this->get(route('articles.show', $article));
    $this->get(route('articles.show', $article));

    expect($article->refresh()->views_count)->toBe(1);
});

it('increments views count for different articles independently', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    $article1 = Article::factory()->published()->create(['issue_id' => $issue->id, 'views_count' => 0]);
    $article2 = Article::factory()->published()->create(['issue_id' => $issue->id, 'views_count' => 0]);

    $this->get(route('articles.show', $article1));
    $this->get(route('articles.show', $article2));

    expect($article1->refresh()->views_count)->toBe(1);
    expect($article2->refresh()->views_count)->toBe(1);
});
