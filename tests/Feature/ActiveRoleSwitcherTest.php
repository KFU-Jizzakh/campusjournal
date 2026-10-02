<?php

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use App\Support\DashboardInbox;
use App\Support\DashboardWatchlist;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    DashboardInbox::flush();
});

test('user can store a valid active role in the session', function () {
    $user = User::factory()->create();
    $user->assignRole('author');
    $user->assignRole('reviewer');

    $this->actingAs($user)
        ->post(route('dashboard.active-role'), ['role' => 'reviewer'])
        ->assertRedirect();

    expect(session('active_role'))->toBe('reviewer');
});

test('unknown role falls back to all in the session', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    $this->actingAs($user)
        ->post(route('dashboard.active-role'), ['role' => 'editor-in-chief']);

    expect(session('active_role'))->toBe('all');
});

test('active role filters inbox task families', function () {
    $user = User::factory()->create();
    $user->assignRole('author');
    $user->assignRole('reviewer');

    Article::factory()->revision()->create([
        'submitted_by' => $user->id,
        'title' => 'Моя доработка',
    ]);

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $user->id,
        'status' => ReviewStatus::Pending,
    ]);

    $combined = DashboardInbox::for($user);
    expect($combined->pluck('task')->all())->toContain('Статья возвращена на доработку')
        ->and($combined->pluck('task')->all())->toContain('Приглашение на рецензирование');

    $asAuthor = DashboardInbox::for($user, 'author');
    expect($asAuthor->pluck('task')->all())->toContain('Статья возвращена на доработку')
        ->and($asAuthor->pluck('task')->all())->not->toContain('Приглашение на рецензирование');

    $asReviewer = DashboardInbox::for($user, 'reviewer');
    expect($asReviewer->pluck('task')->all())->toContain('Приглашение на рецензирование')
        ->and($asReviewer->pluck('task')->all())->not->toContain('Статья возвращена на доработку');
});

test('stale session role falls back to combined tasks', function () {
    $user = User::factory()->create();
    $user->assignRole('author');

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $user->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($user)->withSession(['active_role' => 'reviewer']);

    expect(DashboardInbox::activeRoleFor($user))->toBeNull();
});

test('navigation counter follows the active role filter', function () {
    $user = User::factory()->create();
    $user->assignRole('author');
    $user->assignRole('reviewer');

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $user->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSee('inbox-count');

    $this->actingAs($user)
        ->withSession(['active_role' => 'author'])
        ->get(route('dashboard'))
        ->assertDontSee('inbox-count');
});

test('role switcher is offered to multirole users only', function () {
    $multi = User::factory()->create();
    $multi->assignRole('author');
    $multi->assignRole('reviewer');

    $single = User::factory()->create();
    $single->assignRole('author');

    $this->actingAs($multi)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('name="role"', escape: false)
        ->assertSee('Работать как');

    $this->actingAs($single)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Работать как');
});

test('editorial watchlist hides for author and reviewer active roles', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
        'title' => 'Статья под контролем',
    ]);

    expect(DashboardWatchlist::for($editor))->toHaveCount(1)
        ->and(DashboardWatchlist::for($editor, 'author'))->toBeEmpty()
        ->and(DashboardWatchlist::for($editor, 'reviewer'))->toBeEmpty();
});
