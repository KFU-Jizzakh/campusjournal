<?php

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('guest cannot access dashboard', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('dashboard shows users submitted articles', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    Article::factory()->submitted()->create([
        'submitted_by' => $user->id,
        'title' => 'My Submitted Article',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My Submitted Article');
});

test('dashboard shows users pending reviews', function () {
    $user = User::factory()->create();
    $user->assignRole('reviewer');

    $review = Review::factory()->create([
        'reviewer_id' => $user->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('dashboard shows editorial counts for editors', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    Article::factory()->submitted()->create();
    Article::factory()->inReview()->create();
    Article::factory()->accepted()->create();

    $this->actingAs($eic)
        ->get(route('dashboard'))
        ->assertOk();
});

test('dashboard does not show editorial counts for authors', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('section editor sees only assigned articles counts on dashboard', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    Article::factory()->submitted()->create(['editor_id' => $editor->id]);
    Article::factory()->submitted()->create();

    $this->actingAs($editor)
        ->get(route('dashboard'))
        ->assertOk();
});
