<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    Notification::fake();
});

function submitPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Уникальное название для прогона тестов',
        'abstract_ru' => 'Аннотация на русском языке',
        'category_id' => Category::factory()->create()->id,
        'pdf_file' => UploadedFile::fake()->create('paper.pdf', 1024, 'application/pdf'),
        'author_name' => 'Иванов Иван',
        'author_email' => 'ivanov@example.test',
        'author_phone' => '+7 (900) 123-45-67',
        'author_country' => 'Россия',
        'author_city' => 'Казань',
        'agreement_accepted' => 'on',
    ], $overrides);
}

function submitAs(User $user, array $overrides = [])
{
    return test()->actingAs($user)->post(route('submissions.store'), submitPayload($overrides));
}

test('similar existing title triggers a warning and no article is created', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->published()->create([
        'title' => 'Методы машинного обучения в образовании',
    ]);

    $response = submitAs($author, ['title' => 'Методы машинного обучения в образовании']);

    $response->assertSessionHas('warning')
        ->assertSessionHas('duplicates');

    expect(Article::where('title', 'Методы машинного обучения в образовании')->count())->toBe(1);
});

test('punctuation and case differences are normalized for duplicates', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->published()->create([
        'title' => 'Методы машинного обучения в образовании',
    ]);

    submitAs($author, ['title' => 'методы машинного обучения в образовании!'])
        ->assertSessionHas('warning');
});

test('acknowledged duplicate proceeds with submission', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->published()->create([
        'title' => 'Методы машинного обучения в образовании',
    ]);

    $response = submitAs($author, [
        'title' => 'Методы машинного обучения в образовании',
        'duplicate_acknowledged' => '1',
    ]);

    $response->assertRedirect(route('submissions.show', Article::latest('id')->first()));

    expect(Article::where('title', 'Методы машинного обучения в образовании')->count())->toBe(2);
});

test('unique title passes the duplicate check directly', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->published()->create([
        'title' => 'Методы машинного обучения в образовании',
    ]);

    $response = submitAs($author, ['title' => 'Философия средневекового города']);

    $response->assertSessionMissing('warning');
    expect(Article::where('title', 'Философия средневекового города')->exists())->toBeTrue();
});

test('short titles skip the duplicate check', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->submitted()->create(['title' => 'Введение']);

    submitAs($author, ['title' => 'Введение'])
        ->assertSessionMissing('warning');

    expect(Article::where('title', 'Введение')->count())->toBe(2);
});

// --- The confirming request reuses the parked PDF ---
// File inputs are not repopulated by the warning redirect, so the
// first attempt's upload must survive until confirmation.

test('confirming a duplicate warning submits without re-attaching the pdf', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->published()->create([
        'title' => 'Методы машинного обучения в образовании',
    ]);

    // Step 1: first attempt carries the file and triggers the warning.
    submitAs($author, ['title' => 'Методы машинного обучения в образовании'])
        ->assertSessionHas('warning');

    expect(Article::where('title', 'Методы машинного обучения в образовании')->count())->toBe(1);

    // Step 2: the confirming request sends no file — the parked
    // upload is reused.
    $payload = submitPayload([
        'title' => 'Методы машинного обучения в образовании',
        'duplicate_acknowledged' => '1',
    ]);
    unset($payload['pdf_file']);

    test()->actingAs($author)
        ->post(route('submissions.store'), $payload)
        ->assertRedirect(route('submissions.show', Article::latest('id')->first()));

    $article = Article::where('submitted_by', $author->id)->firstOrFail();

    expect($article->pdf_path)->toStartWith('submissions/')
        ->and(Article::where('title', 'Методы машинного обучения в образовании')->count())->toBe(2);

    Storage::disk('local')->assertExists($article->pdf_path);
});

test('parked pdf is not reused for a different title', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->published()->create([
        'title' => 'Методы машинного обучения в образовании',
    ]);

    submitAs($author, ['title' => 'Методы машинного обучения в образовании'])
        ->assertSessionHas('warning');

    $payload = submitPayload([
        'title' => 'Другое уникальное название рукописи',
        'duplicate_acknowledged' => '1',
    ]);
    unset($payload['pdf_file']);

    test()->actingAs($author)
        ->post(route('submissions.store'), $payload)
        ->assertSessionHasErrors('pdf_file');

    expect(Article::where('title', 'Другое уникальное название рукописи')->exists())->toBeFalse();
});

test('submission without a pdf file is still rejected', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $payload = submitPayload(['title' => 'Совершенно уникальное название работы']);
    unset($payload['pdf_file']);

    test()->actingAs($author)
        ->post(route('submissions.store'), $payload)
        ->assertSessionHasErrors('pdf_file');

    expect(Article::where('submitted_by', $author->id)->exists())->toBeFalse();
});

// --- Confidentiality: only own and published titles may be disclosed ---

test('other authors unpublished submissions are not disclosed as duplicates', function () {
    $other = User::factory()->create();
    $other->assignRole('author');

    // Another author's confidential in-review submission with the
    // very same title must stay invisible to the warning.
    Article::factory()->submitted()->create([
        'title' => 'Методы машинного обучения в образовании',
        'submitted_by' => $other->id,
    ]);

    $author = User::factory()->create();
    $author->assignRole('author');

    submitAs($author, ['title' => 'Методы машинного обучения в образовании'])
        ->assertSessionMissing('warning');

    expect(Article::where('title', 'Методы машинного обучения в образовании')->count())->toBe(2);
});

test('own earlier submission triggers the duplicate warning', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->submitted()->create([
        'title' => 'Методы машинного обучения в образовании',
        'submitted_by' => $author->id,
    ]);

    submitAs($author, ['title' => 'Методы машинного обучения в образовании'])
        ->assertSessionHas('warning')
        ->assertSessionHas('duplicates');
});
