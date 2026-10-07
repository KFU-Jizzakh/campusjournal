<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

function coiEic(): User
{
    $user = User::factory()->create();
    $user->assignRole('editor-in-chief');

    return $user;
}

function coiReviewer(): User
{
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    return $user;
}

// --- Auto-assignment skip ---

test('submitter is never auto-assigned as editor of their own article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $category = Category::factory()->create(['section_editor_id' => $editor->id]);

    $article = Article::submit($editor, [
        'title' => 'Статья самого редактора',
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'pdf_path' => 'submissions/test.pdf',
    ]);

    expect($article->editor_id)->toBeNull();
});

test('rubric auto-assignment is released when the mapped editor is an unclaimed coauthor', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $category = Category::factory()->create(['section_editor_id' => $editor->id]);
    $submitter = User::factory()->create();

    $article = Article::submit($submitter, [
        'title' => 'Статья с редактором-соавтором',
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'pdf_path' => 'submissions/test.pdf',
    ]);

    expect($article->editor_id)->toBe($editor->id);

    $article->syncAuthors($submitter, [
        'full_name' => 'Подавший автор',
        'email' => $submitter->email,
    ], [
        ['full_name' => 'Редактор-соавтор', 'email' => $editor->email],
    ]);

    expect($article->refresh()->editor_id)->toBeNull();
});

test('auto-assignment survives syncing a non-conflicting coauthor', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $category = Category::factory()->create(['section_editor_id' => $editor->id]);
    $submitter = User::factory()->create();

    $article = Article::submit($submitter, [
        'title' => 'Статья с обычным соавтором',
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'pdf_path' => 'submissions/test.pdf',
    ]);

    $article->syncAuthors($submitter, [
        'full_name' => 'Подавший автор',
        'email' => $submitter->email,
    ], [
        ['full_name' => 'Обычный соавтор', 'email' => 'coauthor@example.org'],
    ]);

    expect($article->refresh()->editor_id)->toBe($editor->id);
});

// --- Unclaimed (pivot-email) conflict of interest ---

test('unclaimed coauthor-editor cannot decide on the article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create(['editor_id' => $editor->id]);
    $article->authors()->attach(
        Author::factory()->create()->id,
        ['email' => $editor->email]
    );
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($editor)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'Принял сам себя.',
        ])
        ->assertForbidden();

    expect($article->refresh()->status)->toBe(ArticleStatus::InReview);
});

test('unclaimed coauthor cannot be assigned as section editor', function () {
    $eic = coiEic();
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->submitted()->create();
    $article->authors()->attach(
        Author::factory()->create()->id,
        ['email' => $editor->email]
    );

    $this->actingAs($eic)
        ->post(route('editorial.assign-editor', $article), [
            'editor_id' => $editor->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->refresh()->editor_id)->toBeNull();
});

// --- Self-review guard ---

test('authoring editor is forbidden from assigning any reviewer to their own article', function () {
    $eic = coiEic();
    $reviewer = coiReviewer();
    $article = Article::factory()->submitted()->create(['submitted_by' => $eic->id]);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertForbidden();

    expect($article->reviews()->count())->toBe(0);
});

test('article author cannot be assigned as reviewer even by another editor', function (string $case) {
    $eic = coiEic();
    $author = User::factory()->create();
    $author->assignRole('author');
    $author->assignRole('reviewer');

    $article = Article::factory()->submitted()->create(['submitted_by' => $author->id]);

    $reviewerId = match ($case) {
        'submitter' => $author->id,
        'coauthor' => tap(
            User::factory()->create()->assignRole('reviewer'),
            fn (User $coauthor) => $article->authors()->attach(
                Author::factory()->create(['user_id' => $coauthor->id])->id
            )
        )->id,
        'unclaimed coauthor' => tap(
            User::factory()->create()->assignRole('reviewer'),
            fn (User $coauthor) => $article->authors()->attach(
                Author::factory()->create()->id,
                ['email' => $coauthor->email]
            )
        )->id,
    };

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewerId,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->reviews()->count())->toBe(0);
})->with(['submitter', 'coauthor', 'unclaimed coauthor']);

test('reviewer without authorship can still be assigned', function () {
    $eic = coiEic();
    $reviewer = coiReviewer();
    $article = Article::factory()->submitted()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertSessionHas('success');
});

// --- Workflow policy for authoring editors ---

test('authoring editor-in-chief cannot decide on their own article', function () {
    $eic = coiEic();
    $article = Article::factory()->inReview()->create(['submitted_by' => $eic->id]);
    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'Сам себя принял.',
        ])
        ->assertForbidden();

    expect($article->refresh()->status)->toBe(ArticleStatus::InReview);
});

test('authoring editor-in-chief cannot assign reviewers to their own article', function () {
    $eic = coiEic();
    $reviewer = coiReviewer();
    $article = Article::factory()->submitted()->create(['submitted_by' => $eic->id]);

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertForbidden();
});

test('authoring editor-in-chief cannot rate or cancel reviews on their own article', function () {
    $eic = coiEic();
    $article = Article::factory()->inReview()->create(['submitted_by' => $eic->id]);
    $review = Review::factory()->completed()->create(['article_id' => $article->id]);
    $pending = Review::factory()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.rate-review', ['article' => $article, 'review' => $review]), [
            'quality_rating' => 5,
        ])
        ->assertForbidden();

    $this->actingAs($eic)
        ->post(route('editorial.cancel-review', ['article' => $article, 'review' => $pending]))
        ->assertForbidden();
});

test('authoring editor keeps read-only editorial access', function () {
    $eic = coiEic();
    $article = Article::factory()->inReview()->create(['submitted_by' => $eic->id]);

    $this->actingAs($eic)
        ->get(route('editorial.show', $article))
        ->assertOk();
});

test('non-authoring editor workflow is unaffected', function () {
    $eic = coiEic();
    $reviewer = coiReviewer();
    $article = Article::factory()->inReview()->create();

    $this->actingAs($eic)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertSessionHas('success');

    Review::factory()->completed()->create(['article_id' => $article->id]);

    $this->actingAs($eic)
        ->post(route('editorial.decide', $article), [
            'decision' => 'accept',
            'decision_comments' => 'Норм.',
        ])
        ->assertSessionHas('success');

    expect($article->refresh()->status)->toBe(ArticleStatus::Accepted);
});

// --- Editor assignment guard ---

test('author of the article cannot be assigned as its section editor', function () {
    $eic = coiEic();
    $authorEditor = User::factory()->create();
    $authorEditor->assignRole('section-editor');

    $article = Article::factory()->submitted()->create(['submitted_by' => $authorEditor->id]);

    $this->actingAs($eic)
        ->post(route('editorial.assign-editor', $article), [
            'editor_id' => $authorEditor->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($article->refresh()->editor_id)->toBeNull();
});

// --- Admin override ---

test('admin keeps full override even on their own article', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $reviewer = coiReviewer();

    $article = Article::factory()->submitted()->create(['submitted_by' => $admin->id]);

    $this->actingAs($admin)
        ->post(route('editorial.assign-reviewer', $article), [
            'reviewer_id' => $reviewer->id,
        ])
        ->assertSessionHas('success');
});
