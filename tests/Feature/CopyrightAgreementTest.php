<?php

use App\Enums\ArticleFileLicense;
use App\Models\Article;
use App\Models\ArticleAgreement;
use App\Models\Category;
use App\Models\CopyrightAgreement;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

function caAgreement(): CopyrightAgreement
{
    return CopyrightAgreement::create([
        'version' => 1,
        'title' => 'Лицензионный договор',
        'short_text' => 'Краткий текст.',
        'license' => ArticleFileLicense::CcBy->value,
        'is_active' => true,
        'published_at' => now(),
    ]);
}

function caRevisionArticle(): Article
{
    $author = User::factory()->create();
    $author->assignRole('author');

    return Article::factory()->revision()->create(['submitted_by' => $author->id]);
}

function caRevisionPayload(): array
{
    return [
        'title' => 'Переработанная статья',
        'abstract_ru' => 'Обновлённая аннотация',
        'category_id' => Category::factory()->create()->id,
        'author_name' => 'Иванов Иван',
        'author_email' => 'ivanov@example.test',
        'author_phone' => '+7 (900) 123-45-67',
        'author_country' => 'Россия',
        'author_city' => 'Казань',
        'response_letter' => 'Ответ на замечания.',
        'agreement_accepted' => 'on',
    ];
}

test('revision resubmission requires accepting the agreement again', function () {
    $article = caRevisionArticle();

    $payload = caRevisionPayload();
    unset($payload['agreement_accepted']);

    $this->actingAs($article->submitter)
        ->put(route('submissions.update', $article), $payload)
        ->assertSessionHasErrors('agreement_accepted');

    expect($article->refresh()->agreements)->toHaveCount(0);
});

test('revision resubmission snapshots the current agreement version', function () {
    $agreement = caAgreement();
    $article = caRevisionArticle();

    $this->actingAs($article->submitter)
        ->put(route('submissions.update', $article), caRevisionPayload())
        ->assertRedirect();

    $snapshot = ArticleAgreement::where('article_id', $article->id)->first();

    expect($snapshot)
        ->not->toBeNull()
        ->and($snapshot->copyright_agreement_id)->toBe($agreement->id)
        ->and($snapshot->accepted_by)->toBe($article->submitter->id)
        ->and($snapshot->accepted_ip)->toBe('127.0.0.1');
});

test('author sees the accepted agreement on the submission page', function () {
    $agreement = caAgreement();
    $article = Article::factory()->submitted()->create();

    $article->saveAgreement($agreement, $article->submitter, '127.0.0.1');

    $this->actingAs($article->submitter)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertSee(route('agreements.show', $agreement));
});
