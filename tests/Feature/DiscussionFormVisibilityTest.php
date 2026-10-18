<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

// --- Submission page (author surface) ---

test('coauthor sees discussion threads but no forms on the submission page', function () {
    $submitter = User::factory()->create();
    $coauthor = User::factory()->create();

    $article = Article::factory()->inReview()->create(['submitted_by' => $submitter->id]);
    $article->authors()->attach(
        Author::factory()->create(['user_id' => $coauthor->id])->id,
        ['email' => $coauthor->email]
    );

    $this->actingAs($coauthor)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertDontSee(route('submissions.discussions.store', $article));
});

test('submitter sees the discussion form on the submission page', function () {
    $submitter = User::factory()->create();

    $article = Article::factory()->inReview()->create(['submitted_by' => $submitter->id]);

    $this->actingAs($submitter)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertSee(route('submissions.discussions.store', $article));
});

test('resolved threads hide the reply form from the submitter', function () {
    $submitter = User::factory()->create();

    $article = Article::factory()->inReview()->create(['submitted_by' => $submitter->id]);
    $thread = $article->discussions()->create([
        'user_id' => $submitter->id,
        'scope' => 'article',
        'message' => 'Вопрос',
        'is_resolved' => true,
    ]);

    $response = $this->actingAs($submitter)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertSee($thread->message);

    // The thread is visible, but no reply form may point at it.
    expect($response->getContent())->not->toContain('name="parent_id" value="'.$thread->id.'"');
});

// --- Review page (reviewer surface) ---

test('reviewer sees no message form on the review page', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->inProgress()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertOk()
        ->assertDontSee(route('submissions.discussions.store', $article));
});

// --- Editorial page (editor surface) ---

test('assigned section editor sees the discussion form on the editorial page', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create(['editor_id' => $editor->id]);

    $this->actingAs($editor)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee(route('editorial.discussions.store', $article));
});
