<?php

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    Storage::disk('local')->put('submissions/manuscript.pdf', 'manuscript');
    Storage::disk('local')->put('blinded/blinded.pdf', 'blinded');
    Storage::disk('local')->put('response-letters/letter.pdf', 'letter');
});

function rfaReviewer(): User
{
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    return $user;
}

/**
 * An in-review article with manuscript files and one review of
 * round 1 in the given status, owned by a fresh reviewer.
 *
 * @return array{0: Article, 1: User, 2: Review}
 */
function rfaArticle(ReviewStatus $status): array
{
    $reviewer = rfaReviewer();

    $article = Article::factory()->inReview()->create([
        'pdf_path' => 'submissions/manuscript.pdf',
        'blinded_pdf_path' => 'blinded/blinded.pdf',
    ]);

    $review = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'round' => 1,
        'status' => $status,
    ]);

    return [$article, $reviewer, $review];
}

function rfaLetter(Article $article): void
{
    $article->responseLetters()->create([
        'round' => 1,
        'body' => 'Ответ авторов на замечания.',
        'file_path' => 'response-letters/letter.pdf',
        'uploaded_by' => $article->submitted_by,
    ]);
}

// --- Cancelled reviewers lose access (SPEC-25/BR-4) ---

test('cancelled reviewer cannot download the manuscript or blinded pdf', function () {
    [$article, $reviewer] = rfaArticle(ReviewStatus::Cancelled);

    $this->actingAs($reviewer)
        ->get(route('articles.pdf', $article))
        ->assertNotFound();

    $this->actingAs($reviewer)
        ->get(route('articles.blinded-pdf', $article))
        ->assertNotFound();
});

test('cancelled reviewer cannot read the response letter of their round', function () {
    [$article, $reviewer, $review] = rfaArticle(ReviewStatus::Cancelled);
    rfaLetter($article);

    $this->actingAs($reviewer)
        ->get(route('response-letters.file', $article->responseLetters->first()))
        ->assertForbidden();

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertOk()
        ->assertDontSee('Ответ авторов на замечания.');
});

// --- Declined reviewers lose access ---

test('declined reviewer cannot download the manuscript or blinded pdf', function () {
    [$article, $reviewer] = rfaArticle(ReviewStatus::Declined);

    $this->actingAs($reviewer)
        ->get(route('articles.pdf', $article))
        ->assertNotFound();

    $this->actingAs($reviewer)
        ->get(route('articles.blinded-pdf', $article))
        ->assertNotFound();
});

test('declined reviewer cannot read the response letter of their round', function () {
    [$article, $reviewer, $review] = rfaArticle(ReviewStatus::Declined);
    rfaLetter($article);

    $this->actingAs($reviewer)
        ->get(route('response-letters.file', $article->responseLetters->first()))
        ->assertForbidden();

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertOk()
        ->assertDontSee('Ответ авторов на замечания.');
});

// --- Active reviewers keep access (regression) ---

test('pending reviewer can download the blinded pdf', function () {
    [$article, $reviewer] = rfaArticle(ReviewStatus::Pending);

    $this->actingAs($reviewer)
        ->get(route('articles.blinded-pdf', $article))
        ->assertOk();

    $this->actingAs($reviewer)
        ->get(route('articles.pdf', $article))
        ->assertOk();
});

test('completed reviewer keeps manuscript and response letter access', function () {
    [$article, $reviewer, $review] = rfaArticle(ReviewStatus::Completed);
    rfaLetter($article);

    $this->actingAs($reviewer)
        ->get(route('articles.pdf', $article))
        ->assertOk();

    $this->actingAs($reviewer)
        ->get(route('articles.blinded-pdf', $article))
        ->assertOk();

    $this->actingAs($reviewer)
        ->get(route('response-letters.file', $article->responseLetters->first()))
        ->assertOk();

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertOk()
        ->assertSee('Ответ авторов на замечания.');
});
