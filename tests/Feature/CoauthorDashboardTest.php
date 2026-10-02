<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('dashboard shows coauthored articles to coauthor', function () {
    $submitter = User::factory()->create();
    $submitter->assignRole('author');

    $coauthor = User::factory()->create();
    $coauthor->assignRole('author');

    $article = Article::factory()->submitted()->create([
        'submitted_by' => $submitter->id,
        'title' => 'Совместная статья двух авторов',
    ]);

    $article->authors()->attach(Author::factory()->create(['user_id' => $coauthor->id]));

    $this->actingAs($coauthor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Соавторство')
        ->assertSee('Совместная статья двух авторов');

    $this->actingAs($submitter)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Соавторство');
});

test('coauthored list excludes drafts and own submissions', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $own = Article::factory()->submitted()->create([
        'submitted_by' => $user->id,
        'title' => 'Собственная подача',
    ]);
    $own->authors()->attach(Author::factory()->create(['user_id' => $user->id]));

    $draft = Article::factory()->create([
        'submitted_by' => User::factory()->create()->id,
        'title' => 'Чужой черновик',
    ]);
    $draft->authors()->attach(Author::factory()->create(['user_id' => $user->id]));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Соавторство')
        ->assertSee('Собственная подача');
});

test('user without coauthorship does not see the section', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Соавторство');
});
