<?php

use App\Models\Article;
use App\Models\Category;

test('articles index shows keyword filter indicator when filtering by keyword', function () {
    $this->get(route('articles.index', ['keyword' => 'физика']))
        ->assertOk()
        ->assertSee('Ключевое слово: физика');
});

test('articles index hides keyword filter indicator without keyword filter', function () {
    $this->get(route('articles.index'))
        ->assertOk()
        ->assertDontSee('Ключевое слово:');
});

test('articles index hides keyword filter indicator for an empty keyword parameter', function () {
    $this->get(route('articles.index', ['keyword' => '']))
        ->assertOk()
        ->assertDontSee('Ключевое слово:');
});

test('all-categories chip is not highlighted while a keyword filter is active', function () {
    $category = Category::factory()->create();
    Article::factory()->published()->create(['category_id' => $category->id]);

    $html = $this->get(route('articles.index', ['keyword' => 'физика']))
        ->assertOk()
        ->getContent();

    $this->assertMatchesRegularExpression(
        '/<a\s+href="'.preg_quote(route('articles.index'), '/').'"\s+class="[^"]*bg-gray-100[^"]*">/',
        $html,
    );
});

test('clearing keyword filter keeps the active category filter', function () {
    $category = Category::factory()->create();
    Article::factory()->published()->create(['category_id' => $category->id]);

    $html = $this->get(route('articles.index', ['category' => $category->id, 'keyword' => 'физика']))
        ->assertOk()
        ->getContent();

    $this->assertMatchesRegularExpression(
        '/<a\s+href="'.preg_quote(route('articles.index', ['category' => $category->id]), '/').'"[^>]*title="'.preg_quote(__('pages.articles_clear_filter'), '/').'">/',
        $html,
    );
});

test('articles index filters published articles by keyword', function () {
    Article::factory()->published()->create([
        'title' => 'Статья про кванты',
        'keywords' => ['физика', 'кванты'],
    ]);

    Article::factory()->published()->create([
        'title' => 'Статья про литературу',
        'keywords' => ['литература'],
    ]);

    $this->get(route('articles.index', ['keyword' => 'физика']))
        ->assertOk()
        ->assertSee('Статья про кванты')
        ->assertDontSee('Статья про литературу');
});
