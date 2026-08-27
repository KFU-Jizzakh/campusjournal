<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;

test('articles index hides categories without published articles', function () {
    $category = Category::factory()->create(['name' => 'Пустая категория']);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertDontSee('Пустая категория');
});

test('articles index hides categories that only have draft articles', function () {
    $category = Category::factory()->create(['name' => 'Категория с черновиком']);

    Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Draft,
    ]);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertDontSee('Категория с черновиком');
});

test('articles index shows categories with published articles', function () {
    $category = Category::factory()->create(['name' => 'Популярная категория']);

    Article::factory()->published()->create(['category_id' => $category->id]);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('Популярная категория');
});

test('articles index shows categories with retracted articles', function () {
    $category = Category::factory()->create(['name' => 'Категория с ретрактом']);

    Article::factory()->create([
        'category_id' => $category->id,
        'status' => ArticleStatus::Retracted,
    ]);

    $this->get(route('articles.index'))
        ->assertOk()
        ->assertSee('Категория с ретрактом');
});
