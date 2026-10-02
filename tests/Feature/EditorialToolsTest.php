<?php

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Issue;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('editor sees reviewer workload stats in assign form', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    Review::factory()->completed()->create([
        'reviewer_id' => $reviewer->id,
        'assigned_at' => now()->subDays(12),
        'completed_at' => now()->subDays(4),
    ]);

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::InProgress,
    ]);

    Review::factory()->declined()->create([
        'reviewer_id' => $reviewer->id,
        'assigned_at' => now()->subMonths(3),
    ]);

    $article = Article::factory()->submitted()->create([
        'editor_id' => $editor->id,
    ]);

    $this->actingAs($editor)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee('активных: 2')
        ->assertSee('ср. срок: 8 дн.')
        ->assertSee('отказов за год: 1');
});

test('chief editor sees section editor workload in assign form', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');

    Article::factory()->submitted()->create(['editor_id' => $sectionEditor->id]);
    Article::factory()->inReview()->create(['editor_id' => $sectionEditor->id]);

    $article = Article::factory()->submitted()->create(['editor_id' => null]);

    $this->actingAs($eic)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee('активных: 2', escape: false);
});

test('publisher sees issue assembly with ready articles', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    Issue::factory()->create(['volume' => 3, 'number' => 1, 'year' => 2026]);

    Article::factory()->accepted()->create([
        'title' => 'Принятая без выпуска',
        'issue_id' => null,
    ]);

    $this->actingAs($eic)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Сборка выпуска')
        ->assertSee('Принятая без выпуска');
});

test('issue assembly is hidden from users without publish permission', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $this->actingAs($author)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Сборка выпуска');
});

test('analytics page shows metrics funnel and section load', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $sectionEditor = User::factory()->create();
    $sectionEditor->assignRole('section-editor');

    Article::factory()->submitted()->create([
        'editor_id' => $sectionEditor->id,
        'submitted_at' => now()->subDays(10),
        'decided_at' => now()->subDays(4),
    ]);

    $this->actingAs($eic)
        ->get(route('editorial.stats'))
        ->assertOk()
        ->assertSee('Средний срок до решения')
        ->assertSee('Средний срок рецензирования')
        ->assertSee('Воронка подач')
        ->assertSee('Загрузка редакторов секций')
        ->assertSee('Активных статей: 1');
});

test('analytics page is forbidden for section editors', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $this->actingAs($editor)
        ->get(route('editorial.stats'))
        ->assertForbidden();
});
