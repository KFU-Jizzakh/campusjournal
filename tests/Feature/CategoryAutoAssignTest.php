<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

test('submission is auto-assigned to the section editor mapped to the rubric', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $category = Category::factory()->create(['section_editor_id' => $editor->id]);

    $article = Article::submit(User::factory()->create(), [
        'title' => 'Статья в рубрике',
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'pdf_path' => 'submissions/test.pdf',
    ]);

    expect($article->editor_id)->toBe($editor->id);
});

test('submission without rubric mapping has no editor', function () {
    $category = Category::factory()->create(['section_editor_id' => null]);

    $article = Article::submit(User::factory()->create(), [
        'title' => 'Статья без маппинга',
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'pdf_path' => 'submissions/test.pdf',
    ]);

    expect($article->editor_id)->toBeNull();
});

test('auto-assigned editor can still be reassigned manually', function () {
    $firstEditor = User::factory()->create();
    $firstEditor->assignRole('section-editor');
    $secondEditor = User::factory()->create();
    $secondEditor->assignRole('section-editor');

    $category = Category::factory()->create(['section_editor_id' => $firstEditor->id]);

    $article = Article::submit(User::factory()->create(), [
        'title' => 'Статья',
        'abstract_ru' => 'Аннотация',
        'category_id' => $category->id,
        'pdf_path' => 'submissions/test.pdf',
    ]);

    $article->assignEditor($secondEditor);

    expect($article->refresh()->editor_id)->toBe($secondEditor->id);
});

test('category exposes its default section editor relation', function () {
    $editor = User::factory()->create();
    $category = Category::factory()->create(['section_editor_id' => $editor->id]);

    expect($category->sectionEditor?->id)->toBe($editor->id);
});
