<?php

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Models\Article;
use App\Models\Category;
use App\Models\ResponseLetter;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReRequested;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function rrEic(): User
{
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    return $user;
}

function rrReviewer(): User
{
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    return $user;
}

function rrAuthor(): User
{
    $user = User::factory()->create();
    $user->assignRole('author');

    return $user;
}

function rrAuthorContact(): array
{
    return [
        'author_email' => 'ivanov@example.test',
        'author_phone' => '+7 (900) 123-45-67',
        'author_country' => 'Россия',
        'author_city' => 'Москва',
    ];
}

/**
 * Drive an article through a full round: submitted → in review → completed
 * review → revision decision → resubmitted (round 2).
 */
function rrArticleInRoundTwo(User $author, User $reviewer): Article
{
    $article = Article::factory()->inReview()->create(['submitted_by' => $author->id]);
    Review::factory()->completed()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'round' => 1,
    ]);

    $article->decide('revision', 'Нужна доработка.', rrEic());

    $article->revise([
        'title' => $article->title,
        'abstract_ru' => $article->abstract_ru,
    ]);

    return $article->refresh();
}

// --- Round mechanics ---

test('revise increments current round and clears blinded pdf fields', function () {
    Storage::disk('local')->put('blinded/old.pdf', 'blinded');
    $article = Article::factory()->revision()->create([
        'review_type' => ReviewType::DoubleBlind,
        'blinded_pdf_path' => 'blinded/old.pdf',
        'blinded_at' => now(),
        'blinded_by' => User::factory()->create()->id,
    ]);

    $article->revise(['title' => $article->title, 'abstract_ru' => $article->abstract_ru]);

    expect($article->refresh())
        ->current_round->toBe(2)
        ->status->toBe(ArticleStatus::Submitted)
        ->blinded_pdf_path->toBeNull()
        ->blinded_at->toBeNull()
        ->blinded_by->toBeNull()
        ->needsBlindedPdf()->toBeTrue();

    Storage::disk('local')->assertMissing('blinded/old.pdf');
});

test('reviews are created with the article current round', function () {
    $eic = rrEic();
    $reviewer = rrReviewer();
    $article = Article::factory()->submitted()->create();

    $article->assignReviewer($reviewer, $eic);

    expect($article->reviews()->sole())
        ->round->toBe(1)
        ->status->toBe(ReviewStatus::Pending);
});

// --- Re-assignment in round 2 ---

test('editor can re-assign reviewer who completed an earlier round', function () {
    Notification::fake();

    $eic = rrEic();
    $reviewer = rrReviewer();
    $author = rrAuthor();
    $article = rrArticleInRoundTwo($author, $reviewer);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->reviews()->where('round', 2)->sole())
        ->reviewer_id->toBe($reviewer->id)
        ->status->toBe(ReviewStatus::Pending);

    Notification::assertSentTo($reviewer, ReviewReRequested::class);
});

test('re-request notification is not sent on first-round assignment', function () {
    Notification::fake();

    $eic = rrEic();
    $reviewer = rrReviewer();
    $article = Article::factory()->submitted()->create();

    $article->assignReviewer($reviewer, $eic);

    Notification::assertNotSentTo($reviewer, ReviewReRequested::class);
});

test('duplicate non-declined reviewer in the same round is still rejected', function () {
    $eic = rrEic();
    $reviewer = rrReviewer();
    $article = Article::factory()->inReview()->create();

    $article->assignReviewer($reviewer, $eic);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->reviews()->count())->toBe(1);
});

test('uncompleted reviewer from earlier round does not block re-invite', function () {
    $eic = rrEic();
    $reviewer = rrReviewer();
    $otherReviewer = rrReviewer();
    $author = rrAuthor();

    // The slow reviewer's round 1 assignment stays in progress; another
    // reviewer's completed review allows the revision decision.
    $article = Article::factory()->inReview()->create(['submitted_by' => $author->id]);
    Review::factory()->inProgress()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'round' => 1,
    ]);
    Review::factory()->completed()->create([
        'article_id' => $article->id,
        'reviewer_id' => $otherReviewer->id,
        'round' => 1,
    ]);
    $article->decide('revision', 'Доработать.', $eic);
    $article->revise(['title' => $article->title, 'abstract_ru' => $article->abstract_ru]);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->reviews()->where('round', 2)->sole()->reviewer_id)->toBe($reviewer->id);
});

// --- Decisions require a current-round review ---

test('cannot decide after resubmit until a current round review is completed', function () {
    $eic = rrEic();
    $reviewer = rrReviewer();
    $author = rrAuthor();
    $article = rrArticleInRoundTwo($author, $reviewer);

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'Принято.',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->refresh()->status)->toBe(ArticleStatus::Submitted);
});

test('editor can decide after a current round review is completed', function () {
    $eic = rrEic();
    $reviewer = rrReviewer();
    $author = rrAuthor();
    $article = rrArticleInRoundTwo($author, $reviewer);

    $roundTwoReview = $article->assignReviewer($reviewer, $eic);
    $roundTwoReview->accept();
    $roundTwoReview->complete('accept', 'Всё исправлено.', 'Замечаний нет.');

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'Принято.',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->refresh()->status)->toBe(ArticleStatus::Accepted);
});

// --- Response letter on resubmission ---

test('resubmission requires a response letter', function () {
    $author = rrAuthor();
    $category = Category::factory()->create();
    $article = Article::factory()->revision()->create(['submitted_by' => $author->id]);

    $this->actingAs($author)
        ->from(route('submissions.edit', $article))
        ->put(route('submissions.update', $article), [
            'title' => 'Доработанная статья',
            'abstract_ru' => 'Новая аннотация',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван Иванович',
            ...rrAuthorContact(),
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect(route('submissions.edit', $article))
        ->assertSessionHasErrors('response_letter');

    expect($article->refresh()->status)->toBe(ArticleStatus::Revision);
});

test('resubmission stores response letter in the new round with optional file', function () {
    $author = rrAuthor();
    $category = Category::factory()->create();
    $article = Article::factory()->revision()->create([
        'submitted_by' => $author->id,
        'current_round' => 1,
    ]);

    $file = UploadedFile::fake()->create('response.pdf', 256, 'application/pdf');

    $this->actingAs($author)
        ->put(route('submissions.update', $article), [
            'title' => 'Доработанная статья',
            'abstract_ru' => 'Новая аннотация',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван Иванович',
            ...rrAuthorContact(),
            'agreement_accepted' => 'on',
            'response_letter' => 'Спасибо рецензентам, всё исправлено.',
            'response_letter_file' => $file,
        ])
        ->assertRedirect();

    $article->refresh();

    expect($article->status)->toBe(ArticleStatus::Submitted)
        ->and($article->current_round)->toBe(2);

    $letter = ResponseLetter::query()->where('article_id', $article->id)->sole();

    expect($letter)
        ->round->toBe(2)
        ->body->toBe('Спасибо рецензентам, всё исправлено.')
        ->uploaded_by->toBe($author->id)
        ->file_path->not->toBeNull();

    Storage::disk('local')->assertExists($letter->file_path);
});

// --- Visibility ---

test('editorial page shows round badge for re-review round', function () {
    $eic = rrEic();
    $reviewer = rrReviewer();
    $author = rrAuthor();
    $article = rrArticleInRoundTwo($author, $reviewer);
    Review::factory()->completed()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'round' => 2,
    ]);

    $this->actingAs($eic)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee('Раунд 2');
});

test('reviewer sees response letter and own previous round review', function () {
    $reviewer = rrReviewer();
    $author = rrAuthor();
    $article = rrArticleInRoundTwo($author, $reviewer);
    $article->responseLetters()->create([
        'round' => 2,
        'body' => 'Ответы на замечания первого раунда.',
        'uploaded_by' => $author->id,
    ]);
    $roundTwoReview = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'round' => 2,
    ]);

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $roundTwoReview))
        ->assertOk()
        ->assertSee('Ответы на замечания первого раунда')
        ->assertSee('Ваша рецензия прошлого раунда');
});

test('response letter file download is gated by role', function () {
    $author = rrAuthor();
    $article = Article::factory()->revision()->create(['submitted_by' => $author->id]);
    $letter = $article->responseLetters()->create([
        'round' => 1,
        'body' => 'Письмо.',
        'file_path' => 'response-letters/test.pdf',
        'uploaded_by' => $author->id,
    ]);
    Storage::disk('local')->put('response-letters/test.pdf', 'file contents');

    // Author can download their own letter.
    $this->actingAs($author)
        ->get(route('response-letters.file', $letter))
        ->assertOk();

    // An unrelated user cannot.
    $this->actingAs(rrAuthor())
        ->get(route('response-letters.file', $letter))
        ->assertForbidden();
});

test('assigned reviewer of the round can download the response letter file', function () {
    $reviewer = rrReviewer();
    $author = rrAuthor();
    $article = Article::factory()->inReview()->create(['submitted_by' => $author->id]);
    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'round' => 1,
    ]);
    $letter = $article->responseLetters()->create([
        'round' => 1,
        'body' => 'Письмо.',
        'file_path' => 'response-letters/round1.pdf',
        'uploaded_by' => $author->id,
    ]);
    Storage::disk('local')->put('response-letters/round1.pdf', 'file contents');

    $this->actingAs($reviewer)
        ->get(route('response-letters.file', $letter))
        ->assertOk();
});
