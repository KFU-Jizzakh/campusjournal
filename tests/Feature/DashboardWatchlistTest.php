<?php

use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use App\Support\DashboardWatchlist;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('watchlist shows in-review article waiting on reviewers with nearest deadline', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
        'title' => 'На рецензии у рецензентов',
    ]);

    $near = Review::factory()->inProgress()->create([
        'article_id' => $article->id,
        'review_due_at' => now()->addDays(2),
    ]);

    Review::factory()->inProgress()->create([
        'article_id' => $article->id,
        'review_due_at' => now()->addDays(10),
    ]);

    $watchlist = DashboardWatchlist::for($editor);

    expect($watchlist)->toHaveCount(1)
        ->and($watchlist->first()->title)->toBe('На рецензии у рецензентов')
        ->and($watchlist->first()->task)->toBe('Рецензирование идёт')
        ->and($watchlist->first()->urgency)->toBe('urgent')
        ->and($watchlist->first()->sortDate->toDateTimeString())
        ->toBe($near->review_due_at->toDateTimeString());
});

test('watchlist hides in-review article that editor can decide', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
    ]);

    Review::factory()->completed()->create(['article_id' => $article->id]);

    expect(DashboardWatchlist::for($editor))->toBeEmpty();
});

test('watchlist shows galley with author and revision with author', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    Article::factory()->awaitingApproval()->create([
        'editor_id' => $editor->id,
        'title' => 'Гранки у автора',
        'galley_sent_at' => now()->subDays(3),
    ]);

    Article::factory()->revision()->create([
        'editor_id' => $editor->id,
        'title' => 'Доработка у автора',
        'decided_at' => now()->subDays(20),
    ]);

    $watchlist = DashboardWatchlist::for($editor);

    expect($watchlist)->toHaveCount(2);

    $galley = $watchlist->firstWhere('title', 'Гранки у автора');
    $revision = $watchlist->firstWhere('title', 'Доработка у автора');

    expect($galley->task)->toBe('Гранки у автора на утверждении')
        ->and($galley->deadlineLabel)->toBe('У автора с '.now()->subDays(3)->format('d.m.Y'))
        ->and($revision->task)->toBe('Доработка у автора')
        ->and($revision->urgency)->toBe('warning'); // decided 20 days ago > 14-day threshold
});

test('watchlist is scoped to assigned editor', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $other = User::factory()->create();
    $other->assignRole('section-editor');

    Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
        'title' => 'Чужая для второго редактора',
    ]);

    expect(DashboardWatchlist::for($other))->toBeEmpty();
});

test('watchlist is empty for users without editorial permission', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    expect(DashboardWatchlist::for($author))->toBeEmpty()
        ->and(DashboardWatchlist::deadlines($author))->toBeEmpty();
});

test('deadlines lists overdue and upcoming reviews sorted by due date', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create(['editor_id' => $editor->id]);
    $other = Article::factory()->inReview()->create(['editor_id' => $editor->id]);

    $overdue = Review::factory()->overdue()->create([
        'article_id' => $article->id,
        'reviewer_id' => User::factory()->create(),
    ]);

    $soon = Review::factory()->inProgress()->create([
        'article_id' => $other->id,
        'review_due_at' => now()->addDays(4),
    ]);

    Review::factory()->inProgress()->create([
        'article_id' => $article->id,
        'review_due_at' => now()->addDays(30),
    ]);

    $deadlines = DashboardWatchlist::deadlines($editor);

    expect($deadlines)->toHaveCount(2)
        // Pending invitations are driven by the response deadline,
        // not the review submission deadline.
        ->and($deadlines->first()->sortDate->toDateTimeString())
        ->toBe($overdue->response_due_at->toDateTimeString())
        ->and($deadlines->first()->urgency)->toBe('overdue')
        ->and($deadlines->last()->title)->toBe($other->title);
});

test('dashboard renders watchlist and deadlines sections for editors only', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
        'title' => 'Статья в контроле',
    ]);

    Review::factory()->overdue()->create([
        'article_id' => $article->id,
        'reviewer_id' => User::factory()->create(),
    ]);

    $this->actingAs($editor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Дедлайны рецензий')
        ->assertSee('На контроле')
        ->assertSee('Статья в контроле');

    $author = User::factory()->create();
    $author->assignRole('author');

    $this->actingAs($author)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Дедлайны рецензий')
        ->assertDontSee('На контроле');
});

test('days in status reflects days since last activity', function () {
    $article = Article::factory()->create();

    Carbon::setTestNow(now()->addDays(9));

    expect($article->daysInStatus())->toBe(9);

    Carbon::setTestNow();
});

test('deadlines show response deadline for pending invitation without review due date', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
    ]);

    $invitation = Review::factory()->create([
        'article_id' => $article->id,
        'status' => ReviewStatus::Pending,
        'response_due_at' => now()->addDays(3),
        'review_due_at' => null,
    ]);

    $deadlines = DashboardWatchlist::deadlines($editor);

    expect($deadlines)->toHaveCount(1)
        ->and($deadlines->first()->deadlineLabel)->toBe('Ответ до '.$invitation->response_due_at->format('d.m.Y'))
        ->and($deadlines->first()->urgency)->toBe('urgent')
        ->and($deadlines->first()->sortDate->toDateTimeString())
        ->toBe($invitation->response_due_at->toDateTimeString());
});
