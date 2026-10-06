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

function authorContactFields(array $overrides = []): array
{
    return array_merge([
        'author_email' => 'ivanov@example.test',
        'author_phone' => '+7 (900) 123-45-67',
        'author_country' => 'Россия',
        'author_city' => 'Казань',
        'author_website' => 'https://ivanov.test',
    ], $overrides);
}

function coauthorContactFields(array $overrides = []): array
{
    return array_merge([
        'email' => 'coauthor@example.test',
        'phone' => '+7 (900) 111-22-33',
        'country' => 'Казахстан',
        'city' => 'Алматы',
        'website' => 'https://petrov.test',
    ], $overrides);
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
            ...authorContactFields(),
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

test('submission stores author contact details', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('submissions.store'), [
            'title' => 'Статья с контактами',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(['author_email' => 'contact@example.test', 'author_website' => 'https://ivanov.test']),
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    $primaryAuthor = Author::where('user_id', $author->id)->first();

    expect($primaryAuthor)
        ->not->toBeNull()
        ->email->toBe('contact@example.test')
        ->phone->toBe('+7 (900) 123-45-67')
        ->country->toBe('Россия')
        ->city->toBe('Казань')
        ->website->toBe('https://ivanov.test');

    $pivot = Article::first()->authors()->first()->pivot;

    expect($pivot)
        ->email->toBe('contact@example.test')
        ->phone->toBe('+7 (900) 123-45-67')
        ->country->toBe('Россия')
        ->city->toBe('Казань')
        ->website->toBe('https://ivanov.test');
});

test('submission stores coauthor contact details', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('submissions.store'), [
            'title' => 'Статья с соавтором',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(),
            'coauthors' => [
                ['full_name' => 'Петров Пётр', ...coauthorContactFields(['website' => 'https://petrov.test'])],
            ],
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    $coauthor = Author::where('full_name', 'Петров Пётр')->first();

    expect($coauthor)
        ->not->toBeNull()
        ->email->toBe('coauthor@example.test')
        ->phone->toBe('+7 (900) 111-22-33')
        ->country->toBe('Казахстан')
        ->city->toBe('Алматы')
        ->website->toBe('https://petrov.test');

    $pivot = Article::first()->authors()->where('author_id', $coauthor->id)->first()->pivot;

    expect($pivot)
        ->email->toBe('coauthor@example.test')
        ->phone->toBe('+7 (900) 111-22-33')
        ->country->toBe('Казахстан')
        ->city->toBe('Алматы')
        ->website->toBe('https://petrov.test');
});

test('submission validation requires mandatory fields', function () {
    $this->actingAs(createAuthor())
        ->post(route('submissions.store'), [])
        ->assertSessionHasErrors(['title', 'abstract_ru', 'category_id', 'pdf_file', 'author_name', 'author_email', 'author_phone', 'author_country', 'author_city', 'agreement_accepted']);
});

test('submission rejects invalid phone and country', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('submissions.store'), [
            'title' => 'Статья с ошибками',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(['author_phone' => 'не-номер', 'author_country' => 'Атлантида', 'author_city' => '']),
            'agreement_accepted' => 'on',
        ])
        ->assertSessionHasErrors(['author_phone', 'author_country', 'author_city']);
});

test('submission rejects missing coauthor contact details', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('submissions.store'), [
            'title' => 'Статья с соавтором',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(),
            'coauthors' => [
                ['full_name' => 'Петров Пётр'],
            ],
            'agreement_accepted' => 'on',
        ])
        ->assertSessionHasErrors(['coauthors.0.email', 'coauthors.0.phone', 'coauthors.0.country', 'coauthors.0.city']);
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
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'response_letter' => 'Замечания учтены в полном объёме.',
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
            ...authorContactFields(),
        ])
        ->assertRedirect();

    expect($article->refresh()->status)->toBe(ArticleStatus::Draft);
});

test('update stores changed author contact details', function () {
    $author = createAuthor();
    $category = Category::factory()->create();
    $article = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
        'category_id' => $category->id,
    ]);

    $this->actingAs($author)
        ->put(route('submissions.update', $article), [
            'title' => $article->title,
            'abstract_ru' => $article->abstract_ru ?? 'abstract',
            'category_id' => $category->id,
            'author_name' => 'Иванов Иван',
            ...authorContactFields(['author_email' => 'updated@example.test', 'author_country' => 'Беларусь', 'author_city' => 'Минск']),
        ])
        ->assertRedirect();

    $primaryAuthor = Author::where('user_id', $author->id)->first();

    expect($primaryAuthor)
        ->not->toBeNull()
        ->email->toBe('updated@example.test')
        ->country->toBe('Беларусь')
        ->city->toBe('Минск');

    $pivot = $article->refresh()->authors()->first()->pivot;

    expect($pivot)
        ->email->toBe('updated@example.test')
        ->country->toBe('Беларусь')
        ->city->toBe('Минск')
        ->website->toBe('https://ivanov.test');
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
            ...authorContactFields(),
            'author_orcid' => '0000-0001-2345-6789',
            'coauthors' => [
                ['full_name' => 'Петров Пётр', ...coauthorContactFields(), 'orcid' => '0000-0001-2345-6789'],
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
            ...authorContactFields(),
            'coauthors' => [
                ['full_name' => 'Петров Пётр', ...coauthorContactFields(), 'orcid' => '0000-0002-3456-7890'],
                ['full_name' => 'Сидоров Сидор', ...coauthorContactFields(), 'orcid' => '0000-0002-3456-7890'],
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
            ...authorContactFields(),
            'coauthors' => [
                ['full_name' => 'Петров Пётр', ...coauthorContactFields()],
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
            ...authorContactFields(),
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
            ...authorContactFields(),
            'coauthors' => [
                ['full_name' => 'Сидоров Сидор', ...coauthorContactFields()],
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
            ...authorContactFields(),
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
            ...authorContactFields(),
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
            ...authorContactFields(),
            'keywords' => 'test',
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Петров Пётр',
                    ...coauthorContactFields(),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    Notification::assertSentTo($submitter, AuthorSubmissionReceived::class);
    Notification::assertSentTo($coauthorUser, AuthorSubmissionReceived::class);
});

test('coauthor listing does not overwrite a linked author row', function () {
    $submitter = createAuthor();
    $coauthorUser = createAuthor();
    $category = Category::factory()->create();

    $linked = Author::create([
        'full_name' => 'Петров Пётр Петрович',
        'degree' => 'д.ф.-м.н.',
        'organization' => 'Институт владельца',
        'email' => 'owner@example.test',
        'phone' => '+7 (900) 000-00-00',
        'user_id' => $coauthorUser->id,
        'orcid' => '0000-0002-1234-5678',
    ]);

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Чужая подача с чужим ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван Иванович',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Взломщик Взлом',
                    'organization' => 'Поддельная организация',
                    ...coauthorContactFields(['email' => 'attacker@example.test']),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    $linked->refresh();

    expect($linked->full_name)->toBe('Петров Пётр Петрович')
        ->and($linked->degree)->toBe('д.ф.-м.н.')
        ->and($linked->organization)->toBe('Институт владельца')
        ->and($linked->email)->toBe('owner@example.test')
        ->and($linked->phone)->toBe('+7 (900) 000-00-00')
        ->and($linked->user_id)->toBe($coauthorUser->id);

    $newArticle = Article::where('title', 'Чужая подача с чужим ORCID')->sole();
    $attached = $newArticle->authors()->whereKey($linked->id)->first();

    expect($attached)->not->toBeNull()
        ->and($attached->pivot->email)->toBe('attacker@example.test');
});

test('coauthor listing prefers the linked row when an orcid has both rows', function () {
    $submitter = createAuthor();
    $coauthorUser = createAuthor();
    $category = Category::factory()->create();

    $unlinked = Author::create([
        'full_name' => 'Не-linked строка',
        'email' => 'unlinked@example.test',
        'orcid' => '0000-0002-1234-5678',
    ]);
    $linked = Author::create([
        'full_name' => 'Петров Пётр Петрович',
        'email' => 'owner@example.test',
        'user_id' => $coauthorUser->id,
        'orcid' => '0000-0002-1234-5678',
    ]);

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Подача с дублирующимся ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван Иванович',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Списанный Соавтор',
                    ...coauthorContactFields(['email' => 'listing@example.test']),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    $newArticle = Article::where('title', 'Подача с дублирующимся ORCID')->sole();
    $attachedIds = $newArticle->authors()->pluck('authors.id');

    expect($attachedIds)->toContain($linked->id)
        ->and($attachedIds)->not->toContain($unlinked->id)
        ->and($linked->refresh()->full_name)->toBe('Петров Пётр Петрович')
        ->and($unlinked->refresh()->email)->toBe('unlinked@example.test');
});

test('coauthor listing updates an unlinked row with the latest listing', function () {
    $submitter = createAuthor();
    $category = Category::factory()->create();

    $unlinked = Author::create([
        'full_name' => 'Старое Имя Строки',
        'email' => 'old@example.test',
        'orcid' => '0000-0002-1234-5678',
    ]);

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Подача с уточнённым ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван Иванович',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Новое Имя Строки',
                    ...coauthorContactFields(['email' => 'new@example.test']),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    $unlinked->refresh();

    expect(Author::withTrashed()->where('orcid', '0000-0002-1234-5678')->count())->toBe(1)
        ->and($unlinked->full_name)->toBe('Новое Имя Строки')
        ->and($unlinked->email)->toBe('new@example.test');
});

test('relisting an orcid of a soft-deleted author restores the same row', function () {
    $submitter = createAuthor();
    $category = Category::factory()->create();

    $softDeleted = Author::create([
        'full_name' => 'Удалённый Соавтор',
        'email' => 'deleted@example.test',
        'orcid' => '0000-0002-1234-5678',
    ]);
    $softDeleted->delete();

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Подача с восстановленным ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван Иванович',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Удалённый Соавтор',
                    ...coauthorContactFields(['email' => 'revived@example.test']),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    expect(Author::withTrashed()->where('orcid', '0000-0002-1234-5678')->count())->toBe(1)
        ->and($softDeleted->refresh()->trashed())->toBeFalse()
        ->and($softDeleted->email)->toBe('revived@example.test');
});

test('author can submit a second article reusing their own orcid', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $payload = function (string $title) use ($category) {
        return [
            'title' => $title,
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(),
            'author_orcid' => '0000-0001-2345-6789',
            'agreement_accepted' => 'on',
        ];
    };

    $this->actingAs($author)
        ->post(route('submissions.store'), $payload('Первая статья'))
        ->assertRedirect();

    $this->actingAs($author)
        ->post(route('submissions.store'), $payload('Вторая статья'))
        ->assertRedirect();

    expect(Article::count())->toBe(2);
});

test('author contact details are snapshotted per article', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $submit = function (string $title, string $email, string $phone, string $website) use ($author, $category) {
        $this->actingAs($author)
            ->post(route('submissions.store'), [
                'title' => $title,
                'abstract_ru' => 'Аннотация',
                'category_id' => $category->id,
                'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
                'author_name' => 'Иванов Иван',
                'agreement_accepted' => 'on',
                ...authorContactFields(['author_email' => $email, 'author_phone' => $phone, 'author_website' => $website]),
            ])
            ->assertRedirect();

        return Article::where('title', $title)->first();
    };

    $articleA = $submit('Статья A', 'article-a@example.test', '+7 (900) 111-11-11', 'https://a.test');
    $articleB = $submit('Статья B', 'article-b@example.test', '+7 (900) 222-22-22', 'https://b.test');

    $pivotA = $articleA->authors()->first()->pivot;
    $pivotB = $articleB->authors()->first()->pivot;

    expect($pivotA)
        ->email->toBe('article-a@example.test')
        ->phone->toBe('+7 (900) 111-11-11')
        ->website->toBe('https://a.test');
    expect($pivotB)
        ->email->toBe('article-b@example.test')
        ->phone->toBe('+7 (900) 222-22-22')
        ->website->toBe('https://b.test');

    // The shared authors row mirrors the latest submission, while each
    // article keeps its own snapshot.
    expect(Author::where('user_id', $author->id)->first()->email)
        ->toBe('article-b@example.test');
    expect(Author::where('user_id', $author->id)->first()->website)
        ->toBe('https://b.test');

    // Submission detail page shows the article's own snapshot, not the
    // contact from the newer article.
    $this->actingAs($author)
        ->get(route('submissions.show', $articleA))
        ->assertOk()
        ->assertSee('article-a@example.test')
        ->assertSee('+7 (900) 111-11-11')
        ->assertSee('https://a.test')
        ->assertDontSee('article-b@example.test')
        ->assertDontSee('+7 (900) 222-22-22')
        ->assertDontSee('https://b.test');
});

test('draft edit form prefills the article own contact snapshot', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $articleA = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
        'category_id' => $category->id,
    ]);
    $articleB = Article::factory()->create([
        'submitted_by' => $author->id,
        'status' => ArticleStatus::Draft,
        'category_id' => $category->id,
    ]);

    $payload = fn (string $title, string $email, string $phone, string $website) => [
        'title' => $title,
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'author_name' => 'Иванов Иван',
        ...authorContactFields(['author_email' => $email, 'author_phone' => $phone, 'author_website' => $website]),
    ];

    $this->actingAs($author)
        ->put(route('submissions.update', $articleA), $payload('Черновик A', 'draft-a@example.test', '+7 (900) 111-11-11', 'https://draft-a.test'))
        ->assertRedirect();

    $this->actingAs($author)
        ->put(route('submissions.update', $articleB), $payload('Черновик B', 'draft-b@example.test', '+7 (900) 222-22-22', 'https://draft-b.test'))
        ->assertRedirect();

    $this->actingAs($author)
        ->get(route('submissions.edit', $articleA))
        ->assertOk()
        ->assertSee('draft-a@example.test')
        ->assertSee('+7 (900) 111-11-11')
        ->assertSee('https://draft-a.test')
        ->assertDontSee('draft-b@example.test')
        ->assertDontSee('+7 (900) 222-22-22')
        ->assertDontSee('https://draft-b.test');
});

test('coauthor can submit their own article reusing their orcid', function () {
    $submitter = createAuthor();
    $coauthorUser = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Общая статья',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Петров Пётр',
                    ...coauthorContactFields(),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    // Coauthor ORCID rows are not linked to a user account. They count as
    // claimable by the coauthor themselves: submitting with the same email
    // that was snapshotted on the unlinked row is allowed.
    expect(Author::where('orcid', '0000-0002-1234-5678')->whereNull('user_id')->exists())->toBeTrue();

    $this->actingAs($coauthorUser)
        ->post(route('submissions.store'), [
            'title' => 'Собственная статья',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Петров Пётр',
            ...authorContactFields(['author_email' => 'coauthor@example.test']),
            'author_orcid' => '0000-0002-1234-5678',
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    expect(Article::count())->toBe(2);
    expect(Author::where('orcid', '0000-0002-1234-5678')->where('user_id', $coauthorUser->id)->exists())->toBeTrue();
});

test('user cannot claim an unlinked orcid with a different email', function () {
    $submitter = createAuthor();
    $otherUser = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Общая статья',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Петров Пётр',
                    ...coauthorContactFields(),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    // A different email does not prove ownership of the unlinked ORCID row,
    // so the claim must be rejected.
    $this->actingAs($otherUser)
        ->post(route('submissions.store'), [
            'title' => 'Чужая статья',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Петров Пётр',
            ...authorContactFields(['author_email' => 'other@example.test']),
            'author_orcid' => '0000-0002-1234-5678',
            'agreement_accepted' => 'on',
        ])
        ->assertSessionHasErrors('author_orcid');

    expect(Article::count())->toBe(1);
});

test('user can claim an unlinked orcid row without an email snapshot', function () {
    $submitter = createAuthor();
    $claimer = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Статья с соавтором',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Петров Пётр',
                    ...coauthorContactFields(),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    // Legacy rows have a NULL email snapshot; they cannot prove ownership
    // either way and must not permanently block a claim.
    Author::where('orcid', '0000-0002-1234-5678')->whereNull('user_id')->update(['email' => null]);

    $this->actingAs($claimer)
        ->post(route('submissions.store'), [
            'title' => 'Заявка на чужой ORCID без email',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Петров Пётр',
            ...authorContactFields(['author_email' => 'claimer@example.test']),
            'author_orcid' => '0000-0002-1234-5678',
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    expect(Article::count())->toBe(2);
});

test('user can reuse their orcid after the unlinked row email diverges', function () {
    $submitter = createAuthor();
    $owner = createAuthor();
    $thirdSubmitter = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($submitter)
        ->post(route('submissions.store'), [
            'title' => 'Статья с соавтором-владельцем ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Петров Пётр',
                    ...coauthorContactFields(['email' => 'owner@example.test']),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    // The coauthor claims the ORCID by submitting with the same email.
    $this->actingAs($owner)
        ->post(route('submissions.store'), [
            'title' => 'Собственная статья владельца ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Петров Пётр',
            ...authorContactFields(['author_email' => 'owner@example.test']),
            'author_orcid' => '0000-0002-1234-5678',
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    // A later submission lists the coauthor with a different email, which
    // rewrites the unlinked row and diverges it from the claimed one.
    $this->actingAs($thirdSubmitter)
        ->post(route('submissions.store'), [
            'title' => 'Статья с соавтором по рассинхроненному email',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Сидоров Сидор',
            ...authorContactFields(['author_email' => 'third@example.test']),
            'agreement_accepted' => 'on',
            'coauthors' => [
                [
                    'full_name' => 'Петров Пётр',
                    ...coauthorContactFields(['email' => 'third-listing@example.test']),
                    'orcid' => '0000-0002-1234-5678',
                ],
            ],
        ])
        ->assertRedirect();

    // The owner still submits with their own ORCID, now from a new email.
    $this->actingAs($owner)
        ->post(route('submissions.store'), [
            'title' => 'Вторая статья владельца ORCID',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Петров Пётр',
            ...authorContactFields(['author_email' => 'owner-new@example.test']),
            'author_orcid' => '0000-0002-1234-5678',
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    expect(Article::count())->toBe(4);
    expect(Author::where('orcid', '0000-0002-1234-5678')->where('user_id', $owner->id)->exists())->toBeTrue();
});

test('submission page shows author contact details when pivot snapshot is missing', function () {
    $author = createAuthor();
    $category = Category::factory()->create();

    $this->actingAs($author)
        ->post(route('submissions.store'), [
            'title' => 'Статья для проверки контактов',
            'abstract_ru' => 'Аннотация',
            'category_id' => $category->id,
            'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
            'author_name' => 'Иванов Иван',
            ...authorContactFields(),
            'agreement_accepted' => 'on',
        ])
        ->assertRedirect();

    $article = Article::first();

    // Legacy rows predating the contact snapshot have a NULL pivot snapshot;
    // the detail page must fall back to the shared authors row.
    $article->authors()->updateExistingPivot($article->authors->first()->id, [
        'email' => null,
        'phone' => null,
        'country' => null,
        'city' => null,
        'website' => null,
    ]);

    $this->actingAs($author)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertSee('ivanov@example.test')
        ->assertSee('https://ivanov.test');
});
