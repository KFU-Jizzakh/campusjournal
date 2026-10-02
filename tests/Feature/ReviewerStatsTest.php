<?php

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Models\User;
use App\Support\ReviewerStats;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('reviewer stats aggregate workload and reliability', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    Review::factory()->completed()->create([
        'reviewer_id' => $reviewer->id,
        'assigned_at' => now()->subDays(10),
        'completed_at' => now()->subDays(2),
    ]);

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    Review::factory()->overdue()->create([
        'reviewer_id' => $reviewer->id,
    ]);

    Review::factory()->declined()->create([
        'reviewer_id' => $reviewer->id,
        'assigned_at' => now()->subMonths(2),
    ]);

    Review::factory()->declined()->create([
        'reviewer_id' => $reviewer->id,
        'assigned_at' => now()->subYears(2),
    ]);

    $stats = ReviewerStats::map(collect([$reviewer]));

    expect($stats[$reviewer->id])
        ->active->toBe(2)
        ->overdue->toBe(1)
        ->completed->toBe(1)
        ->avg_days->toBe(8)
        ->declines_year->toBe(1);
});

test('reviewer stats map is empty for empty collection', function () {
    expect(ReviewerStats::map(collect()))->toBe([]);
});

test('reviewer stats are isolated per reviewer', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    Review::factory()->completed()->create([
        'reviewer_id' => $first->id,
        'assigned_at' => now()->subDays(4),
        'completed_at' => now(),
    ]);

    $stats = ReviewerStats::map(collect([$first, $second]));

    expect($stats[$first->id]['completed'])->toBe(1)
        ->and($stats[$second->id]['completed'])->toBe(0)
        ->and($stats[$second->id]['avg_days'])->toBeNull();
});
