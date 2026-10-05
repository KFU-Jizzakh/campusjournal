<?php

use App\Models\Article;
use App\Models\User;
use App\Support\ReviewerMatcher;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

function matcherReviewer(array $interests): User
{
    $user = User::factory()->create();
    $user->assignRole('reviewer');
    $user->profile()->updateOrCreate(
        ['user_id' => $user->id],
        ['last_name' => 'Рецензент', 'first_name' => fake()->firstName(), 'interests' => $interests]
    );

    return $user;
}

test('matcher counts keyword interest intersections case insensitively', function () {
    $article = Article::factory()->submitted()->create(['keywords' => ['РКИ', 'Методика', 'оценка']]);
    $matching = matcherReviewer(['рки', ' Методика ', 'перевод']);
    $partial = matcherReviewer(['методика']);
    $none = matcherReviewer(['лингвистика']);
    $noInterests = matcherReviewer([]);

    $result = ReviewerMatcher::match($article, collect([$matching, $partial, $none, $noInterests]));

    expect($result)
        ->toBe([
            $matching->id => 2,
            $partial->id => 1,
        ]);
});

test('matcher returns empty when article has no keywords', function () {
    $article = Article::factory()->submitted()->create(['keywords' => null]);
    $reviewer = matcherReviewer(['РКИ']);

    expect(ReviewerMatcher::match($article, collect([$reviewer])))->toBe([]);
});

test('matcher counts duplicate keywords only once', function () {
    $article = Article::factory()->submitted()->create(['keywords' => ['РКИ', 'рки', ' РКИ ']]);
    $reviewer = matcherReviewer(['РКИ', 'рки']);

    expect(ReviewerMatcher::match($article, collect([$reviewer])))->toBe([$reviewer->id => 1]);
});

test('assignment options show match badge only for reviewers with matches', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $matching = matcherReviewer(['РКИ', 'методика']);
    $other = matcherReviewer(['нейронауки']);

    $article = Article::factory()->submitted()->create(['keywords' => ['РКИ']]);

    $this->actingAs($eic)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee('совпадений: 1')
        ->assertDontSee('совпадений: 0');
});
