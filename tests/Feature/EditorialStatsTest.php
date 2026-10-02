<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use App\Support\EditorialStats;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('funnel counts non-draft articles by status', function () {
    Article::factory()->submitted()->create();
    Article::factory()->inReview()->create();
    Article::factory()->inReview()->create();
    Article::factory()->create(['status' => ArticleStatus::Draft]);

    $funnel = EditorialStats::funnel();

    expect($funnel['submitted'])->toBe(1)
        ->and($funnel['in_review'])->toBe(2)
        ->and($funnel['draft'])->toBe(0);
});

test('average days to decision uses decided articles only', function () {
    Article::factory()->create([
        'status' => ArticleStatus::Accepted,
        'submitted_at' => now()->subDays(20),
        'decided_at' => now()->subDays(5),
    ]);

    Article::factory()->submitted()->create();

    expect(EditorialStats::avgDaysToDecision())->toBe(15);
});

test('average reviewer turnaround uses completed reviews only', function () {
    expect(EditorialStats::avgReviewerTurnaround())->toBeNull();

    Review::factory()->completed()->create([
        'assigned_at' => now()->subDays(6),
        'completed_at' => now(),
    ]);

    expect(EditorialStats::avgReviewerTurnaround())->toBe(6);
});

test('section editor load counts active articles per editor', function () {
    $first = User::factory()->create();
    $first->assignRole('section-editor');

    $second = User::factory()->create();
    $second->assignRole('section-editor');

    Article::factory()->submitted()->create(['editor_id' => $first->id]);
    Article::factory()->inReview()->create(['editor_id' => $first->id]);
    Article::factory()->published()->create(['editor_id' => $first->id]);
    Article::factory()->accepted()->create(['editor_id' => $second->id]);

    $load = collect(EditorialStats::sectionEditorLoad())->keyBy(fn (array $row) => $row['user']->id);

    expect($load[$first->id]['active'])->toBe(2)
        ->and($load[$second->id]['active'])->toBe(1);
});
