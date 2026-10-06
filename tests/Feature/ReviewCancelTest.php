<?php

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\OutboxEvent;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewCancelled;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => $this->seed(RoleSeeder::class));

function cancelEic(): User
{
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    return $user;
}

test('editor can cancel a pending review assignment', function () {
    Notification::fake();

    $eic = cancelEic();
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->create([
        'article_id' => $article->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.cancel-review', ['article' => $article, 'review' => $review]))
        ->assertRedirect()
        ->assertSessionHas('success');

    $review->refresh();

    expect($review->status)->toBe(ReviewStatus::Cancelled)
        ->and(OutboxEvent::where('name', 'review.cancelled')->count())->toBe(1);

    Notification::assertSentTo($review->reviewer, ReviewCancelled::class);
});

test('editor can cancel an in-progress review assignment', function () {
    $eic = cancelEic();
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->inProgress()->create([
        'article_id' => $article->id,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.cancel-review', ['article' => $article, 'review' => $review]))
        ->assertSessionHas('success');

    expect($review->refresh()->status)->toBe(ReviewStatus::Cancelled);
});

test('terminal reviews cannot be cancelled', function (ReviewStatus $status) {
    $eic = cancelEic();
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->create([
        'article_id' => $article->id,
        'status' => $status,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.cancel-review', ['article' => $article, 'review' => $review]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($review->refresh()->status)->toBe($status);
})->with([
    ReviewStatus::Completed,
    ReviewStatus::Declined,
    ReviewStatus::Cancelled,
]);

test('reviewer and author cannot cancel reviews', function () {
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->create([
        'article_id' => $article->id,
        'status' => ReviewStatus::Pending,
    ]);

    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $this->actingAs($reviewer)
        ->post(route('editorial.cancel-review', ['article' => $article, 'review' => $review]))
        ->assertForbidden();

    $author = User::factory()->create();
    $author->assignRole('author');

    $this->actingAs($author)
        ->post(route('editorial.cancel-review', ['article' => $article, 'review' => $review]))
        ->assertForbidden();
});

test('cancel route rejects a review from another article', function () {
    $eic = cancelEic();
    $article = Article::factory()->inReview()->create();
    $otherArticle = Article::factory()->inReview()->create();
    $review = Review::factory()->create([
        'article_id' => $otherArticle->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.cancel-review', ['article' => $article, 'review' => $review]))
        ->assertNotFound();
});

test('cancelled reviewer can be re-invited in the same round', function () {
    $eic = cancelEic();
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.cancel-review', ['article' => $article, 'review' => $review]))
        ->assertSessionHas('success');

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->reviews()->where('round', 1)->count())->toBe(2)
        ->and($article->reviews()->where('status', ReviewStatus::Pending)->count())->toBe(1);
});

test('cancelled review leaves the active list and appears in the history list', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $article = Article::factory()->inReview()->create();
    $cancelled = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Cancelled,
    ]);
    $active = Review::factory()->inProgress()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)
        ->get(route('reviews.index'))
        ->assertOk()
        ->assertSee('Отменена');

    expect($active->refresh()->status)->toBe(ReviewStatus::InProgress)
        ->and($cancelled->refresh()->status)->toBe(ReviewStatus::Cancelled);
});
