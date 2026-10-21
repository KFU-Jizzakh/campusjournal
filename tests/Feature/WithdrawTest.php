<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;

test('author can withdraw their own pre-published article', function () {
    $author = User::factory()->create();
    $author->assignRole('author');
    $article = Article::factory()->submitted()->create(['submitted_by' => $author->id]);

    $this->actingAs($author)
        ->post(route('submissions.withdraw', $article), ['reason' => 'Отзыв по личным обстоятельствам.'])
        ->assertRedirect(route('dashboard'));

    expect($article->refresh())
        ->status->toBe(ArticleStatus::Withdrawn)
        ->withdrawal_reason->toBe('Отзыв по личным обстоятельствам.')
        ->withdrawn_at->not->toBeNull();
});

test('author cannot withdraw someone else`s article', function () {
    $article = Article::factory()->submitted()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('submissions.withdraw', $article), ['reason' => 'Чужая статья.'])
        ->assertForbidden();

    expect($article->refresh()->status)->toBe(ArticleStatus::Submitted);
});

test('managing editor can withdraw any pre-published article', function () {
    $me = User::factory()->create();
    $me->assignRole('managing-editor');

    $article = Article::factory()->inReview()->create();

    $this->actingAs($me)
        ->post(route('editorial.withdraw', $article), ['reason' => 'Дубликат подачи.'])
        ->assertRedirect();

    expect($article->refresh()->status)->toBe(ArticleStatus::Withdrawn);
});

test('section editor cannot withdraw an unassigned article', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->submitted()->create();

    $this->actingAs($editor)
        ->post(route('editorial.withdraw', $article), ['reason' => 'Не моя статья.'])
        ->assertForbidden();

    expect($article->refresh()->status)->toBe(ArticleStatus::Submitted);
});

test('published article cannot be withdrawn', function () {
    $author = User::factory()->create();
    $author->assignRole('author');
    $article = Article::factory()->published()->create(['submitted_by' => $author->id]);

    $this->actingAs($author)
        ->post(route('submissions.withdraw', $article), ['reason' => 'Поздно.'])
        ->assertSessionHas('error');

    expect($article->refresh()->status)->toBe(ArticleStatus::Published);
});

test('withdrawn article is hidden from the public site', function () {
    $article = Article::factory()->submitted()->create(['title' => 'Отозванная рукопись']);
    $article->withdraw('Причина.', User::factory()->create());

    $this->get(route('articles.show', $article))->assertNotFound();
    $this->get(route('articles.index'))->assertOk()->assertDontSee('Отозванная рукопись');
});
