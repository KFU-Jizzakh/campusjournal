<?php

use App\Enums\DiscussionScope;
use App\Models\Article;
use App\Models\Author;
use App\Models\Discussion;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('visibleTo scope shows leadership all discussions', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->submitted()->create();

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Тред для главного редактора',
    ]);

    expect(Discussion::visibleTo($eic)->pluck('id'))->toContain($discussion->id);
});

test('visibleTo scope limits section editor to assigned articles', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $otherEditor = User::factory()->create();
    $otherEditor->assignRole('section-editor');

    $mine = Article::factory()->submitted()->create(['editor_id' => $editor->id]);
    $theirs = Article::factory()->submitted()->create(['editor_id' => $otherEditor->id]);

    $visible = Discussion::factory()->create([
        'article_id' => $mine->id,
        'scope' => DiscussionScope::Article,
    ]);

    $hidden = Discussion::factory()->create([
        'article_id' => $theirs->id,
        'scope' => DiscussionScope::Article,
    ]);

    $ids = Discussion::visibleTo($editor)->pluck('id');

    expect($ids)->toContain($visible->id)->not->toContain($hidden->id);
});

test('visibleTo scope shows review bound threads to the reviewer', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $other = User::factory()->create();
    $other->assignRole('reviewer');

    $article = Article::factory()->submitted()->create();

    $review = Review::factory()->inProgress()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
    ]);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'review_id' => $review->id,
        'scope' => DiscussionScope::Article,
    ]);

    $idsForReviewer = Discussion::visibleTo($reviewer)->pluck('id');
    $idsForOther = Discussion::visibleTo($other)->pluck('id');

    expect($idsForReviewer)->toContain($discussion->id);
    expect($idsForOther)->not->toContain($discussion->id);
});

test('visibleTo scope shows article scope threads to credited coauthor', function () {
    $submitter = User::factory()->create();
    $submitter->assignRole('author');

    $coauthorUser = User::factory()->create();
    $coauthorUser->assignRole('author');

    $article = Article::factory()->submitted()->create([
        'submitted_by' => $submitter->id,
    ]);

    $article->authors()->attach(Author::factory()->create([
        'user_id' => $coauthorUser->id,
    ])->id);

    $discussion = Discussion::factory()->create([
        'article_id' => $article->id,
        'scope' => DiscussionScope::Article,
    ]);

    expect(Discussion::visibleTo($coauthorUser)->pluck('id'))->toContain($discussion->id);
});

test('unreadBy scope excludes threads marked as read', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $article = Article::factory()->submitted()->create([
        'submitted_by' => $user->id,
    ]);

    $unread = Discussion::factory()->create([
        'article_id' => $article->id,
        'scope' => DiscussionScope::Article,
    ]);

    $read = Discussion::factory()->create([
        'article_id' => $article->id,
        'scope' => DiscussionScope::Article,
    ]);
    $read->readBy($user);

    $ids = Discussion::unreadBy($user)->pluck('id');

    expect($ids)->toContain($unread->id)->not->toContain($read->id);
});
