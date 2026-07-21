<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\CopyrightAgreement;
use App\Models\User;
use App\Notifications\AuthorSubmissionReceived;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
    Storage::fake('local');

    if (! CopyrightAgreement::exists()) {
        CopyrightAgreement::create([
            'version' => 1,
            'title' => 'Test Agreement',
            'short_text' => 'Test short text.',
            'is_active' => true,
            'published_at' => now(),
        ]);
    }
});

function createAuthor(): User
{
    $user = User::factory()->create();
    $user->assignRole('author');

    return $user;
}

test('author can view submission create form', function () {
    $this->actingAs(createAuthor())
        ->get(route('submissions.create'))
        ->assertOk();
});

test('guest cannot access submissions', function () {
    $this->get(route('submissions.create'))
        ->assertRedirect(route('login'));
});

test('user without permission cannot access submissions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('submissions.create'))
        ->assertForbidden();
});

test('author can submit an article', function () {
    Notification::fake();

    $author = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('submissions.store'), [
            'title' => 'Тестовая статья',
            'abstract_ru' => 'Аннотация на русском языке',
            'abstract_en' => 'English abstract',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            'author_degree' => 'к.н.',
            'author_position' => 'доцент',
            'author_organization' => 'КФУ',
            'author_orcid' => '0000-0001-2345-6789',
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    $article = Article::first();
    expect($article)
        ->title->toBe('Тестовая статья')
        ->status->toBe(ArticleStatus::Submitted)
        ->submitted_by->toBe($author->id);

    expect($article->authors)->toHaveCount(1);
    Storage::disk('local')->assertExists($article->pdf_path);

    Notification::assertSentTo($author, AuthorSubmissionReceived::class);
});

test('submission validation requires mandatory fields', function () {
    $this->actingAs(createAuthor())
        ->post(route('submissions.store'), [])
        ->assertSessionHasErrors(['title', 'abstract_ru', 'category_id', 'pdf_file', 'author_name', 'agreement_accepted']);
});

test('author can view own submission', function () {
    $author = createAuthor();
    $article = Article::factory()->submitted()->create(['submitted_by' => $author->id]);

    $this->actingAs($author)
        ->get(route('submissions.show', $article))
        ->assertOk();
});

test('author cannot view another users submission', function () {
    $author = createAuthor();
    $article = Article::factory()->submitted()->create();

    $this->actingAs($author)
        ->get(route('submissions.show', $article))
        ->assertForbidden();
});

test('author can edit own draft article', function () {
    $author = createAuthor();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
    ]);

    $this->actingAs($author)
        ->get(route('submissions.edit', $article))
        ->assertOk();
});

test('author cannot edit submitted article', function () {
    $author = createAuthor();
    $article = Article::factory()->submitted()->create(['submitted_by' => $author->id]);

    $this->actingAs($author)
        ->get(route('submissions.edit', $article))
        ->assertForbidden();
});

test('author can edit article in revision status', function () {
    $author = createAuthor();
    $article = Article::factory()->revision()->create(['submitted_by' => $author->id]);
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->put(route('submissions.update', $article), [
            'title' => 'Обновлённая статья',
            'abstract_ru' => 'Новая аннотация',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван Иванович',
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    $article->refresh();
    expect($article)
        ->title->toBe('Обновлённая статья')
        ->status->toBe(ArticleStatus::Submitted)
        ->decision->toBeNull()
        ->decision_comments->toBeNull();
});

test('updating draft article does not change status to submitted', function () {
    $author = createAuthor();
    $category = Category::factory()->create();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
        'category_id' => $category->id,
    ]);

    $this->actingAs($author)
        ->put(route('submissions.update', $article), [
            'title' => 'Updated',
            'abstract_ru' => 'Updated abstract',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван Иванович',
        ])
        ->assertRedirect();

    expect($article->refresh()->status)->toBe(ArticleStatus::Draft);
});

test('submission rejects duplicate orcid between author and coauthor', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('submissions.store'), [
            'title' => 'Статья с дублем ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            'author_orcid' => '0000-0001-2345-6789',
            'coauthors' => [
                ['full_name' => 'Петров Пётр', 'orcid' => '0000-0001-2345-6789'],
            ],
            'agreement_accepted' => 'on',
        ])
        ->assertSessionHasErrors('coauthors');
});

test('submission rejects duplicate orcid between coauthors', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('submissions.store'), [
            'title' => 'Статья с дублем ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            'coauthors' => [
                ['full_name' => 'Петров Пётр', 'orcid' => '0000-0002-3456-7890'],
                ['full_name' => 'Сидоров Сидор', 'orcid' => '0000-0002-3456-7890'],
            ],
            'agreement_accepted' => 'on',
        ])
        ->assertSessionHasErrors('coauthors');
});

test('updating article cleans up orphaned coauthors', function () {
    $author = createAuthor();
    $category = Category::factory()->create();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
        'category_id' => $category->id,
    ]);

    // First submit with a coauthor
    $this->actingAs($author)
        ->put(route('submissions.update', $article), [
            'title' => $article->title,
            'abstract_ru' => $article->abstract_ru ?? 'abstract',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван',
            'coauthors' => [
                ['full_name' => 'Петров Пётр'],
            ],
        ])
        ->assertRedirect();

    $oldCoauthor = Author::where('full_name', 'Петров Пётр')->first();
    expect($oldCoauthor)->not->toBeNull();

    // Update without the coauthor
    $this->actingAs($author)
        ->put(route('submissions.update', $article), [
            'title' => $article->title,
            'abstract_ru' => $article->abstract_ru ?? 'abstract',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван',
        ])
        ->assertRedirect();

    // The orphaned coauthor should be deleted
    expect(Author::find($oldCoauthor->id))->toBeNull();
});

test('coauthor shared with another article is not deleted', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $article1 = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
        'category_id' => $category->id,
    ]);
    $article2 = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
        'category_id' => $category->id,
    ]);

    // Submit both articles with the same coauthor name
    $this->actingAs($author)
        ->put(route('submissions.update', $article1), [
            'title' => $article1->title,
            'abstract_ru' => $article1->abstract_ru ?? 'abstract',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван',
            'coauthors' => [
                ['full_name' => 'Сидоров Сидор'],
            ],
        ]);

    $coauthor1 = Author::where('full_name', 'Сидоров Сидор')->first();

    // Attach the same coauthor to article2
    $article2->authors()->attach($coauthor1->id, ['order' => 2]);

    // Now remove coauthor from article1
    $this->actingAs($author)
        ->put(route('submissions.update', $article1), [
            'title' => $article1->title,
            'abstract_ru' => $article1->abstract_ru ?? 'abstract',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван',
        ]);

    // Coauthor is still attached to article2, so should NOT be deleted
    expect(Author::find($coauthor1->id))->not->toBeNull();
});

test('pdf replacement deletes old file', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    Storage::disk('public')->put('submissions/old.pdf', 'old content');

    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
        'pdf_path' => 'submissions/old.pdf',
        'category_id' => $category->id,
    ]);

    $this->actingAs($author)
        ->put(route('submissions.update', $article), [
            'title' => $article->title,
            'abstract_ru' => $article->abstract_ru,
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван Иванович',
            'pdf_file' => UploadedFile::fake()->create('new.pdf', 512, 'application/pdf'),
        ])
        ->assertRedirect();

    Storage::disk('public')->assertMissing('submissions/old.pdf');
    Storage::disk('local')->assertExists($article->refresh()->pdf_path);
});

test('submission notifies coauthor linked via ORCID', function () {
    Notification::fake();

    $submitter = createAuthor();
    $coauthorUser = createAuthor();
    $category = Category::factory()->create();

    $coauthorAuthor = Author::create([
        'full_name' => 'Петров Пётр',
        'user_id' => $coauthorUser->id,
        'orcid' => '0000-0002-1234-5678',
    ]);

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Test Article',
            'abstract_ru' => 'Abstract',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 100, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            'keywords' => 'test',
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Петров Пётр',
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    Notification::assertSentTo($submitter, AuthorSubmissionReceived::class);
    Notification::assertSentTo($coauthorUser, AuthorSubmissionReceived::class);
});
