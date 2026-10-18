<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function fundingAuthor(): User
{
    $user = User::factory()->create();
    $user->assignRole('author');

    return $user;
}

function fundingPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Статья с финансированием',
        'abstract_ru' => 'Аннотация',
        'category_id' => Category::factory()->create()->id,
        'pdf_file' => UploadedFile::fake()->create('paper.pdf', 512, 'application/pdf'),
        'author_name' => 'Иванов Иван',
        'author_email' => 'ivanov@example.test',
        'author_phone' => '+7 (900) 123-45-67',
        'author_country' => 'Россия',
        'author_city' => 'Казань',
        'agreement_accepted' => 'on',
    ], $overrides);
}

test('submission stores funding entries on the article', function () {
    $author = fundingAuthor();

    $this->actingAs($author)
        ->post(route('submissions.store'), fundingPayload([
            'funding' => [
                ['funder_name' => 'РНФ', 'funder_identifier' => 'https://ror.org/00x7h1v84', 'award_number' => '23-45-67890'],
            ],
        ]))
        ->assertRedirect();

    $article = Article::where('submitted_by', $author->id)->firstOrFail();

    expect($article->funding)->toBeArray()
        ->and($article->funding[0]['funder_name'])->toBe('РНФ')
        ->and($article->funding[0]['award_number'])->toBe('23-45-67890');
});

test('funding is optional', function () {
    $author = fundingAuthor();

    $this->actingAs($author)
        ->post(route('submissions.store'), fundingPayload())
        ->assertRedirect();

    expect(Article::where('submitted_by', $author->id)->firstOrFail()->funding)->toBeNull();
});

test('funding entry without a funder name is rejected', function () {
    $author = fundingAuthor();

    $this->actingAs($author)
        ->post(route('submissions.store'), fundingPayload([
            'funding' => [
                ['funder_name' => '', 'award_number' => '123'],
            ],
        ]))
        ->assertSessionHasErrors('funding.0.funder_name');

    expect(Article::where('submitted_by', $author->id)->exists())->toBeFalse();
});

test('author replaces funding when revising', function () {
    $author = fundingAuthor();
    $article = Article::factory()->revision()->create([
        'submitted_by' => $author->id,
        'funding' => [['funder_name' => 'Старый фонд']],
    ]);

    $this->actingAs($author)
        ->put(route('submissions.update', $article), fundingPayload([
            'title' => $article->title,
            'funding' => [
                ['funder_name' => 'Новый фонд'],
            ],
            'response_letter' => 'Ответ на замечания рецензентов.',
        ]))
        ->assertRedirect();

    $article = $article->refresh();

    expect($article->status->value)->toBe('submitted')
        ->and($article->funding)->toBe([['funder_name' => 'Новый фонд']]);
});
