<?php

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('reviews page shows reviewer workload summary chips', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    Review::factory()->completed()->create([
        'reviewer_id' => $reviewer->id,
        'assigned_at' => now()->subDays(6),
        'completed_at' => now(),
    ]);

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    Review::factory()->declined()->create([
        'reviewer_id' => $reviewer->id,
        'assigned_at' => now()->subMonths(1),
    ]);

    $this->actingAs($reviewer)
        ->get(route('reviews.index'))
        ->assertOk()
        ->assertSee('Активных рецензий: 1')
        ->assertSee('Завершено: 1')
        ->assertSee('Средний срок: 6 дн.')
        ->assertSee('Отказов за год: 1');
});

test('reviews page separates completed reviews with completion date', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $completed = Review::factory()->completed()->create([
        'reviewer_id' => $reviewer->id,
        'completed_at' => now()->subDays(3),
    ]);

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($reviewer)
        ->get(route('reviews.index'))
        ->assertOk()
        ->assertSee('Завершённые рецензии')
        ->assertSee($completed->article?->title)
        ->assertSee($completed->completed_at?->format('d.m.Y'));
});

test('reviews page handles reviewer without history', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $this->actingAs($reviewer)
        ->get(route('reviews.index'))
        ->assertOk()
        ->assertSee('Активных рецензий: 0')
        ->assertSee('Средний срок: —')
        ->assertSee('Вам пока не назначены рецензии.');
});
