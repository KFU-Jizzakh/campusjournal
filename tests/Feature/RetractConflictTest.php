<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

test('leadership author cannot retract their own published article', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $article = Article::factory()->published()->create(['submitted_by' => $eic->id]);

    expect($eic->can('retract', $article))->toBeFalse();

    $this->actingAs($eic)
        ->post(route('editorial.retract', $article), ['reason' => 'Собственная статья.'])
        ->assertForbidden();

    expect($article->refresh()->status)->toBe(ArticleStatus::Published);
});

test('unconflicted leadership can retract a published article', function () {
    $author = User::factory()->create();

    $article = Article::factory()->published()->create(['submitted_by' => $author->id]);

    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    expect($eic->can('retract', $article))->toBeTrue();
});

test('admin override stands even for an article authored by the admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $article = Article::factory()->published()->create(['submitted_by' => $admin->id]);

    expect($admin->can('retract', $article))->toBeTrue();
});

test('retraction stays leadership-only', function () {
    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');

    $article = Article::factory()->published()->create(['editor_id' => $sectionEditor->id]);

    expect($sectionEditor->can('retract', $article))->toBeFalse();
});
