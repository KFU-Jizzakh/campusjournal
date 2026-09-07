<?php

use App\Models\Article;
use App\Models\Author;
use Illuminate\Support\Facades\Schema;

test('contact snapshot migration backfills legacy article_author rows', function () {
    $migration = require base_path('database/migrations/2026_09_08_093427_add_contact_snapshot_to_article_author_table.php');

    // Roll back to the pre-change schema so the backfill can run
    // against rows created without contact columns.
    $migration->down();
    expect(Schema::hasColumn('article_author', 'email'))->toBeFalse();

    $author = Author::create([
        'full_name' => 'Иванов Иван',
        'email' => 'ivanov@example.test',
        'phone' => '+7 (900) 123-45-67',
        'country' => 'Россия',
        'city' => 'Казань',
        'website' => 'https://ivanov.test',
    ]);
    $authorWithoutContacts = Author::create([
        'full_name' => 'Петров Пётр',
    ]);
    $article = Article::factory()->create();
    $article->authors()->attach([
        $author->id => ['order' => 1],
        $authorWithoutContacts->id => ['order' => 2],
    ]);

    $migration->up();

    $pivot = $article->authors()->where('author_id', $author->id)->first()->pivot;
    expect($pivot)
        ->email->toBe('ivanov@example.test')
        ->phone->toBe('+7 (900) 123-45-67')
        ->country->toBe('Россия')
        ->city->toBe('Казань')
        ->website->toBe('https://ivanov.test');

    $emptyPivot = $article->authors()->where('author_id', $authorWithoutContacts->id)->first()->pivot;
    expect($emptyPivot)
        ->email->toBeNull()
        ->phone->toBeNull()
        ->country->toBeNull()
        ->city->toBeNull()
        ->website->toBeNull();
});
