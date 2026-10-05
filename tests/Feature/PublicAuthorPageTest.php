<?php

use App\Models\Article;
use App\Models\Author;
use Database\Seeders\AuthorSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('article list links author names to the public profile', function () {
    $this->seed(AuthorSeeder::class);

    $author = Author::where('full_name', 'Галимов Алмаз Мирзанурович')->first();

    $article = Article::factory()->published()->create();
    $article->authors()->attach($author->id, ['order' => 1]);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee(route('authors.show', $author), escape: false);
});

test('public author page renders profile and publications', function () {
    $this->seed(AuthorSeeder::class);

    $author = Author::where('full_name', 'Колесникова Галина Ивановна')->first();

    $article = Article::factory()->published()->create(['title' => 'Публикация Колесниковой']);
    $article->authors()->attach($author->id, ['order' => 1]);

    $this->get(route('authors.show', $author))
        ->assertOk()
        ->assertSee('Колесникова Галина Ивановна')
        ->assertSee('Публикация Колесниковой')
        ->assertSee('доктор философских наук');
});

test('author seeder generates avatar files on the public disk', function () {
    Storage::disk('public')->deleteDirectory('authors/photos');

    $this->seed(AuthorSeeder::class);

    $galimov = Author::where('full_name', 'Галимов Алмаз Мирзанурович')->first();

    expect($galimov->photo_path)->toBe('authors/photos/galimov.svg')
        ->and(Storage::disk('public')->exists($galimov->photo_path))->toBeTrue();

    $svg = Storage::disk('public')->get($galimov->photo_path);

    expect($svg)->toContain('<svg')->toContain('ГА');
});

test('author page falls back to initials when no photo', function () {
    $author = Author::factory()->create(['full_name' => 'Безфотова Анна', 'photo_path' => null]);

    $this->get(route('authors.show', $author))
        ->assertOk()
        ->assertSee('Безфотова Анна');
});
