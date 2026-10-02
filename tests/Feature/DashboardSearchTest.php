<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('dashboard article search finds submission by coauthor full name', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $article = Article::factory()->submitted()->create([
        'submitted_by' => $author->id,
        'title' => 'Исследование климатических рисков',
    ]);

    $article->authors()->attach(Author::factory()->create([
        'full_name' => 'Иванова Анна Петровна',
    ])->id);

    $this->actingAs($author)
        ->get(route('dashboard', ['q' => 'иванова']))
        ->assertOk()
        ->assertSee('Исследование климатических рисков');
});

test('dashboard article search finds submission by title', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->submitted()->create([
        'submitted_by' => $author->id,
        'title' => 'Уникальное название для поиска',
    ]);

    $this->actingAs($author)
        ->get(route('dashboard', ['q' => 'уникальное']))
        ->assertOk()
        ->assertSee('Уникальное название для поиска');
});

test('dashboard article search excludes non-matching submissions', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->submitted()->create([
        'submitted_by' => $author->id,
        'title' => 'Не подходящая под запись статья',
    ]);

    $this->actingAs($author)
        ->get(route('dashboard', ['q' => 'несуществующийзапрос']))
        ->assertOk()
        ->assertDontSee('Не подходящая под запись статья');
});
