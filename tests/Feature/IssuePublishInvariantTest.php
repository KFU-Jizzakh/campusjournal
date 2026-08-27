<?php

use App\Enums\ArticleStatus;
use App\Exceptions\IssueNotPublishedException;
use App\Exceptions\IssueUnpublishFailedException;
use App\Models\Article;
use App\Models\Issue;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('publish into planned issue throws and leaves article approved', function () {
    $article = Article::factory()->approved()->create();
    $issue = Issue::factory()->create(['status' => 'planned']);

    expect(fn () => $article->publish($issue))->toThrow(IssueNotPublishedException::class);

    $article->refresh();
    expect($article->status)->toBe(ArticleStatus::Approved);
    expect($article->issue_id)->toBeNull();
    expect($article->published_at)->toBeNull();
});

test('publish into in-progress issue throws', function () {
    $article = Article::factory()->approved()->create();
    $issue = Issue::factory()->create(['status' => 'in_progress']);

    expect(fn () => $article->publish($issue))->toThrow(IssueNotPublishedException::class);
});

test('saving published article against planned issue throws', function () {
    $issue = Issue::factory()->create(['status' => 'planned']);

    expect(fn () => Article::factory()->published()->create(['issue_id' => $issue->id]))
        ->toThrow(IssueNotPublishedException::class);
});

test('moving published article to planned issue throws', function () {
    $published = Issue::factory()->create(['status' => 'published']);
    $planned = Issue::factory()->create(['status' => 'planned']);
    $article = Article::factory()->published()->create(['issue_id' => $published->id]);

    expect(fn () => $article->update(['issue_id' => $planned->id]))
        ->toThrow(IssueNotPublishedException::class);

    expect($article->fresh()->issue_id)->toBe($published->id);
});

test('moving retracted article to planned issue throws', function () {
    $published = Issue::factory()->create(['status' => 'published']);
    $planned = Issue::factory()->create(['status' => 'planned']);
    $article = Article::factory()->published()->create(['issue_id' => $published->id]);

    expect(fn () => $article->update(['issue_id' => $planned->id, 'status' => ArticleStatus::Retracted]))
        ->toThrow(IssueNotPublishedException::class);
});

test('cannot unpublish issue containing published articles', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create(['issue_id' => $issue->id]);

    expect(fn () => $issue->update(['status' => 'planned']))
        ->toThrow(IssueUnpublishFailedException::class);

    expect($issue->fresh()->status)->toBe('published');
});

test('cannot unpublish issue containing only retracted articles', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create([
        'issue_id' => $issue->id,
        'status' => ArticleStatus::Retracted,
    ]);

    expect(fn () => $issue->update(['status' => 'planned']))
        ->toThrow(IssueUnpublishFailedException::class);

    expect($issue->fresh()->status)->toBe('published');
});

test('cannot switch issue to in-progress while it contains published articles', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    Article::factory()->published()->create(['issue_id' => $issue->id]);

    expect(fn () => $issue->update(['status' => 'in_progress']))
        ->toThrow(IssueUnpublishFailedException::class);
});

test('can unpublish issue without published articles', function () {
    $issue = Issue::factory()->create(['status' => 'published']);

    $issue->update(['status' => 'planned']);

    expect($issue->fresh()->status)->toBe('planned');
});

test('can publish planned issue', function () {
    $issue = Issue::factory()->create(['status' => 'planned']);

    $issue->update(['status' => 'published']);

    expect($issue->fresh()->status)->toBe('published');
});
