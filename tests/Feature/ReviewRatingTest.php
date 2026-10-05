<?php

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use App\Support\ReviewerStats;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

function ratingEic(): User
{
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    return $user;
}

test('editor can rate a completed review', function () {
    $eic = ratingEic();
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.rate-review', ['article' => $article, 'review' => $review]), [
            'quality_rating' => 4,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $review->refresh();

    expect($review)
        ->quality_rating->toBe(4)
        ->rated_by->toBe($eic->id)
        ->rated_at->not->toBeNull();
});

test('rating a non-completed review is rejected', function () {
    $eic = ratingEic();
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->create([
        'article_id' => $article->id,
        'status' => ReviewStatus::InProgress,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.rate-review', ['article' => $article, 'review' => $review]), [
            'quality_rating' => 5,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($review->refresh()->quality_rating)->toBeNull();
});

test('quality rating must be between 1 and 5', function (int $rating) {
    $eic = ratingEic();
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.rate-review', ['article' => $article, 'review' => $review]), [
            'quality_rating' => $rating,
        ])
        ->assertSessionHasErrors('quality_rating');
})->with([0, 6]);

test('reviewer cannot rate reviews', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($reviewer)
        ->post(route('editorial.rate-review', ['article' => $article, 'review' => $review]), [
            'quality_rating' => 5,
        ])
        ->assertForbidden();
});

test('rating route rejects a review from another article', function () {
    $eic = ratingEic();
    $article = Article::factory()->inReview()->create();
    $otherArticle = Article::factory()->inReview()->create();
    $review = Review::factory()->completed()->create(['article_id' => $otherArticle->id]);

    $this->actingAs($eic)
        ->post(route('editorial.rate-review', ['article' => $article, 'review' => $review]), [
            'quality_rating' => 5,
        ])
        ->assertNotFound();
});

test('re-rating overwrites the previous rating', function () {
    $eic = ratingEic();
    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->completed()->create(['article_id' => $article->id]);

    $review->rate(2, $eic);
    $review->rate(5, $eic);

    expect($review->refresh()->quality_rating)->toBe(5);
});

test('reviewer stats include average rating of completed reviews', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    Review::factory()->completed()->create(['reviewer_id' => $reviewer->id, 'quality_rating' => 4, 'rated_by' => User::factory()->create()->id, 'rated_at' => now()]);
    Review::factory()->completed()->create(['reviewer_id' => $reviewer->id, 'quality_rating' => 5, 'rated_by' => User::factory()->create()->id, 'rated_at' => now()]);
    Review::factory()->completed()->create(['reviewer_id' => $reviewer->id]);

    $stats = ReviewerStats::map(collect([$reviewer]))[$reviewer->id];

    expect($stats['avg_rating'])->toBe(4.5);
});

test('average rating is null when reviewer has no rated reviews', function () {
    $reviewer = User::factory()->create();

    $stats = ReviewerStats::single($reviewer);

    expect($stats['avg_rating'])->toBeNull();
});

test('editorial page shows rating badge but reviewer page does not', function () {
    $eic = ratingEic();
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $article = Article::factory()->inReview()->create();
    $review = Review::factory()->completed()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'quality_rating' => 4,
        'rated_by' => $eic->id,
        'rated_at' => now(),
    ]);

    $this->actingAs($eic)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee('★ 4/5');

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertOk()
        ->assertDontSee('★ 4/5');
});
