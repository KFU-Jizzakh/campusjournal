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

    Article::factory()->submitted()->create([
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

    Article::factory()->submitted()->create([
        'title' => 'Методы машинного обучения в образовании',
    ]);

    submitAs($author, ['title' => 'методы машинного обучения в образовании!'])
        ->assertSessionHas('warning');
});

test('acknowledged duplicate proceeds with submission', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->submitted()->create([
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

    Article::factory()->submitted()->create([
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
