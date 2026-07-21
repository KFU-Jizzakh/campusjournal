<?php

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewCompleted;
use App\Notifications\ReviewerAccepted;
use App\Notifications\ReviewerDeclined;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createReviewerUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    return $user;
}

test('reviewer can view their reviews list', function () {
    $reviewer = createReviewerUser();

    $this->actingAs($reviewer)
        ->get(route('reviews.index'))
        ->assertOk();
});

test('reviewer can view own review', function () {
    $reviewer = createReviewerUser();
    $review = Review::factory()->create(['reviewer_id' => $reviewer->id]);

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertOk();
});

test('reviewer cannot view another users review', function () {
    $reviewer = createReviewerUser();
    $review = Review::factory()->create();

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertForbidden();
});

test('reviewer can submit a review', function () {
    $reviewer = createReviewerUser();
    $review = Review::factory()->inProgress()->create(['reviewer_id' => $reviewer->id]);

    $this->actingAs($reviewer)
        ->put(route('reviews.update', $review), [
            'recommendation' => 'accept',
            'comments_for_editor' => 'Хорошая статья, рекомендую к публикации.',
            'comments_for_author' => 'Работа выполнена на высоком уровне.',
        ])
        ->assertRedirect(route('reviews.index'));

    $review->refresh();
    expect($review)
        ->status->toBe(ReviewStatus::Completed)
        ->recommendation->toBe('accept')
        ->completed_at->not->toBeNull();
});

test('reviewer cannot update completed review', function () {
    $reviewer = createReviewerUser();
    $review = Review::factory()->completed()->create(['reviewer_id' => $reviewer->id]);

    $this->actingAs($reviewer)
        ->put(route('reviews.update', $review), [
            'recommendation' => 'reject',
            'comments_for_editor' => 'Changed mind',
            'comments_for_author' => 'Changed mind',
        ])
        ->assertForbidden();
});

test('reviewer cannot submit review without accepting first', function () {
    $reviewer = createReviewerUser();
    $review = Review::factory()->create(['reviewer_id' => $reviewer->id]);

    $this->actingAs($reviewer)
        ->put(route('reviews.update', $review), [
            'recommendation' => 'accept',
            'comments_for_editor' => 'test',
            'comments_for_author' => 'test',
        ])
        ->assertForbidden();
});

test('reviewer cannot update another users review', function () {
    $reviewer = createReviewerUser();
    $review = Review::factory()->create();

    $this->actingAs($reviewer)
        ->put(route('reviews.update', $review), [
            'recommendation' => 'accept',
            'comments_for_editor' => 'test',
            'comments_for_author' => 'test',
        ])
        ->assertForbidden();
});

test('review validation requires all fields', function () {
    $reviewer = createReviewerUser();
    $review = Review::factory()->inProgress()->create(['reviewer_id' => $reviewer->id]);

    $this->actingAs($reviewer)
        ->put(route('reviews.update', $review), [])
        ->assertSessionHasErrors(['recommendation', 'comments_for_editor', 'comments_for_author']);
});

test('review recommendation must be valid', function () {
    $reviewer = createReviewerUser();
    $review = Review::factory()->inProgress()->create(['reviewer_id' => $reviewer->id]);

    $this->actingAs($reviewer)
        ->put(route('reviews.update', $review), [
            'recommendation' => 'invalid',
            'comments_for_editor' => 'test',
            'comments_for_author' => 'test',
        ])
        ->assertSessionHasErrors('recommendation');
});

test('guest cannot access reviews', function () {
    $this->get(route('reviews.index'))
        ->assertRedirect(route('login'));
});

test('user without review permission cannot access reviews', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $this->actingAs($user)
        ->get(route('reviews.index'))
        ->assertForbidden();
});

// ==========================================
// SPEC-03 AC-2: Editor receives notification when reviewer accepts
// ==========================================

test('editor receives ReviewerAccepted notification when reviewer accepts', function () {
    Notification::fake();

    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $reviewer = createReviewerUser();

    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'editor_id' => $sectionEditor->id,
    ]);

    $review = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'assigned_by' => $eic->id,
    ]);

    $review->accept();

    Notification::assertSentTo($eic, ReviewerAccepted::class);
    Notification::assertNotSentTo($sectionEditor, ReviewerAccepted::class);
});

// ==========================================
// SPEC-03 AC-3: Editor receives notification when reviewer declines
// ==========================================

test('editor receives ReviewerDeclined notification when reviewer declines', function () {
    Notification::fake();

    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $reviewer = createReviewerUser();

    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'editor_id' => $sectionEditor->id,
    ]);

    $review = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'assigned_by' => $eic->id,
    ]);

    $review->decline();

    Notification::assertSentTo($eic, ReviewerDeclined::class);
    Notification::assertNotSentTo($sectionEditor, ReviewerDeclined::class);
});

// ==========================================
// SPEC-03 AC-4: Editor receives notification when review is completed
// ==========================================

test('editor receives ReviewCompleted notification when review is completed', function () {
    Notification::fake();

    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');
    $reviewer = createReviewerUser();

    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'editor_id' => $sectionEditor->id,
    ]);

    $review = Review::factory()->inProgress()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'assigned_by' => $eic->id,
    ]);

    $review->complete('accept', 'Good article', 'Well written');

    Notification::assertSentTo($eic, ReviewCompleted::class);
    Notification::assertNotSentTo($sectionEditor, ReviewCompleted::class);
});
