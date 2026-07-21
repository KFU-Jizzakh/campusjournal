<?php

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Enums\ReviewType;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    Storage::fake('local');

    $this->createEic = function () {
        $user = User::factory()->create();
        $user->assignRole('editor-in-chief');

        return $user;
    };

    $this->createSectionEditor = function () {
        $user = User::factory()->create();
        $user->assignRole('section-editor');

        return $user;
    };

    $this->createReviewer = function () {
        $user = User::factory()->create();
        $user->assignRole('reviewer');

        return $user;
    };

    $this->createAuthor = function () {
        $user = User::factory()->create();
        $user->assignRole('author');

        return $user;
    };
});

// ==========================================
// AC-1: Editor can view/change review type (no reviewers assigned yet)
// BR-1: Type locked after first non-declined reviewer
// ==========================================

test('editor can change review type to double-blind when no reviewers assigned', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'review_type' => ReviewType::SingleBlind,
    ]);

    $this->actingAs($eic)
        ->put(route('editorial.set-review-type', $article), ['review_type' => 'double_blind'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->fresh()->review_type)->toBe(ReviewType::DoubleBlind);
});

test('editor cannot change review type when active reviewers exist', function () {
    $eic = ($this->createEic)();
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'review_type' => ReviewType::SingleBlind,
    ]);

    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($eic)
        ->put(route('editorial.set-review-type', $article), ['review_type' => 'double_blind'])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->fresh()->review_type)->toBe(ReviewType::SingleBlind);
});

test('editor can change review type when only declined reviewers exist', function () {
    $eic = ($this->createEic)();
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'review_type' => ReviewType::SingleBlind,
    ]);

    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Declined,
    ]);

    $this->actingAs($eic)
        ->put(route('editorial.set-review-type', $article), ['review_type' => 'double_blind'])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->fresh()->review_type)->toBe(ReviewType::DoubleBlind);
});

// ==========================================
// AC-2: Double-blind → anonymized upload block appears
// AC-3: Upload anonymized PDF → indicator shows file + date
// ==========================================

test('editor can upload blinded pdf for double-blind article', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $pdf = UploadedFile::fake()->create('blinded.pdf', 500, 'application/pdf');

    $this->actingAs($eic)
        ->post(route('editorial.upload-blinded-pdf', $article), [
            'blinded_pdf' => $pdf,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->blinded_pdf_path)->not->toBeNull();
    expect($article->blinded_at)->not->toBeNull();
    expect($article->blinded_by)->not->toBeNull();
    Storage::disk('local')->assertExists($article->blinded_pdf_path);
});

test('upload blinded pdf rejects non-pdf file', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $file = UploadedFile::fake()->create('image.png', 500, 'image/png');

    $this->actingAs($eic)
        ->post(route('editorial.upload-blinded-pdf', $article), [
            'blinded_pdf' => $file,
        ])
        ->assertSessionHasErrors('blinded_pdf');

    expect($article->fresh()->blinded_pdf_path)->toBeNull();
});

// ==========================================
// AC-4: Editor can replace anonymized file
// ==========================================

test('editor can replace blinded pdf', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $firstPdf = UploadedFile::fake()->create('blinded1.pdf', 500, 'application/pdf');

    $this->actingAs($eic)
        ->post(route('editorial.upload-blinded-pdf', $article), [
            'blinded_pdf' => $firstPdf,
        ]);

    $firstPath = $article->fresh()->blinded_pdf_path;
    Storage::disk('local')->assertExists($firstPath);

    $secondPdf = UploadedFile::fake()->create('blinded2.pdf', 500, 'application/pdf');

    $this->actingAs($eic)
        ->post(route('editorial.upload-blinded-pdf', $article), [
            'blinded_pdf' => $secondPdf,
        ]);

    $article->refresh();
    expect($article->blinded_pdf_path)->not->toBe($firstPath);

    // Old file is deleted from disk
    Storage::disk('local')->assertMissing($firstPath);
    // New file exists
    Storage::disk('local')->assertExists($article->blinded_pdf_path);
});

// ==========================================
// AC-5: Delete anonymized file only if no active reviewers
// BR-3: No delete of anonymized PDF with active reviewers
// ==========================================

test('editor can delete blinded pdf when no active reviewers', function () {
    $eic = ($this->createEic)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $pdf = UploadedFile::fake()->create('blinded.pdf', 500, 'application/pdf');

    $this->actingAs($eic)
        ->post(route('editorial.upload-blinded-pdf', $article), [
            'blinded_pdf' => $pdf,
        ]);

    $blindedPath = $article->fresh()->blinded_pdf_path;

    $this->actingAs($eic)
        ->delete(route('editorial.delete-blinded-pdf', $article))
        ->assertRedirect()
        ->assertSessionHas('success');

    $article->refresh();
    expect($article->blinded_pdf_path)->toBeNull();
    Storage::disk('local')->assertMissing($blindedPath);
});

test('editor cannot delete blinded pdf when active reviewers exist', function () {
    $eic = ($this->createEic)();
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $pdf = UploadedFile::fake()->create('blinded.pdf', 500, 'application/pdf');
    $path = $pdf->store('submissions', 'local');
    $article->update([
        'blinded_pdf_path' => $path,
        'blinded_at' => now(),
        'blinded_by' => $eic->id,
    ]);

    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::InProgress,
    ]);

    $this->actingAs($eic)
        ->delete(route('editorial.delete-blinded-pdf', $article))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->fresh()->blinded_pdf_path)->not->toBeNull();
});

// ==========================================
// AC-6 / BR-4: Double-blind → reviewer gets anonymized PDF
// ==========================================

test('double-blind reviewer gets blinded pdf link', function () {
    $eic = ($this->createEic)();
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $pdf = UploadedFile::fake()->create('blinded.pdf', 500, 'application/pdf');
    $path = $pdf->store('submissions', 'local');
    $article->update([
        'blinded_pdf_path' => $path,
        'blinded_at' => now(),
        'blinded_by' => $eic->id,
    ]);

    $review = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::InProgress,
    ]);

    $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertOk()
        ->assertSee('Анонимизированная версия');
});

// ==========================================
// AC-7 / BR-6: Double-blind → author name hidden from reviewer everywhere
// ==========================================

test('double-blind hides author name from reviewer', function () {
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $review = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::InProgress,
    ]);

    $response = $this->actingAs($reviewer)
        ->get(route('reviews.show', $review))
        ->assertOk();

    $authorName = $article->submitter->full_name;
    $response->assertDontSee($authorName);
});

// ==========================================
// AC-8: Type change hidden after reviewer assignment
// ==========================================

test('review type change section hidden when active reviewers exist', function () {
    $eic = ($this->createEic)();
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::InReview,
        'review_type' => ReviewType::SingleBlind,
    ]);

    Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $response = $this->actingAs($eic)
        ->get(route('editorial.show', $article))
        ->assertOk();

    $response->assertDontSee('изменить тип рецензирования');
});

// ==========================================
// AC-9: Author/editor always get original manuscript
// BR-5: Author never sees reviewer name
// ==========================================

test('author article page does not show blinded pdf reference', function () {
    $author = ($this->createAuthor)();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'review_type' => ReviewType::DoubleBlind,
        'pdf_path' => 'articles/original.pdf',
    ]);

    $this->actingAs($author)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertDontSee('Анонимизированная версия');
});

test('author never sees reviewer identity', function () {
    $author = ($this->createAuthor)();
    $reviewer = ($this->createReviewer)();
    $eic = ($this->createEic)();

    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::InReview,
        'editor_id' => $eic->id,
    ]);

    $review = Review::factory()->create([
        'article_id' => $article->id,
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Completed,
    ]);

    $article->update(['decision' => 'accept', 'decided_by' => $eic->id, 'decided_at' => now()]);

    $reviewerName = $reviewer->full_name;

    $response = $this->actingAs($author)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertDontSee($reviewerName);
});

// ==========================================
// BR-2: No reviewer assignment on double-blind without anonymized PDF
// ==========================================

test('cannot assign reviewer on double-blind article without blinded pdf', function () {
    $eic = ($this->createEic)();
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->reviews)->toHaveCount(0);
});

test('can assign reviewer on double-blind article with blinded pdf', function () {
    $eic = ($this->createEic)();
    $reviewer = ($this->createReviewer)();
    $article = Article::factory()->create([
        'status' => ArticleStatus::Submitted,
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $pdf = UploadedFile::fake()->create('blinded.pdf', 500, 'application/pdf');

    $this->actingAs($eic)
        ->post(route('editorial.upload-blinded-pdf', $article), [
            'blinded_pdf' => $pdf,
        ]);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($article->reviews)->toHaveCount(1);
});
