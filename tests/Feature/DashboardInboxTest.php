<?php

use App\Enums\DiscussionScope;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Discussion;
use App\Models\Review;
use App\Models\User;
use App\Support\DashboardInbox;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('inbox shows resubmit task for author with revision article', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->revision()->create([
        'submitted_by' => $author->id,
        'title' => 'Возвращённая на доработку',
    ]);

    $this->actingAs($author)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Возвращённая на доработку')
        ->assertSee('Статья возвращена на доработку');
});

test('inbox shows galley approval task with inline approve form', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $article = Article::factory()->awaitingApproval()->create([
        'submitted_by' => $author->id,
        'title' => 'Статья на утверждении гранок',
    ]);

    $this->actingAs($author)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Гранки ожидают вашего утверждения')
        ->assertSee(route('submissions.approve-galley', $article));
});

test('inbox shows draft submission task for author', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    Article::factory()->create([
        'submitted_by' => $author->id,
        'title' => 'Неподанный черновик',
    ]);

    $this->actingAs($author)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Черновик не подан');
});

test('inbox shows review invitation with inline accept and decline forms', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $review = Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($reviewer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Приглашение на рецензирование')
        ->assertSee(route('reviews.accept', $review))
        ->assertSee(route('reviews.decline', $review));
});

test('inbox sorts overdue review response before fresh invitation', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    Review::factory()->overdue()->create([
        'reviewer_id' => $reviewer->id,
    ]);

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($reviewer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Ответ просрочен', 'Ответ до']);
});

test('inbox shows write review task for in-progress review', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    Review::factory()->inProgress()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
    ]);

    $this->actingAs($reviewer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Требуется написать рецензию');
});

test('section editor sees assign reviewer task only for own articles', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $otherEditor = User::factory()->create();
    $otherEditor->assignRole('section-editor');

    Article::factory()->submitted()->create([
        'editor_id' => $editor->id,
        'title' => 'Статья моей секции',
    ]);

    $this->actingAs($editor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Назначьте рецензентов');

    $this->actingAs($otherEditor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Статья моей секции');
});

test('editor sees decision task when article has completed review', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
        'title' => 'Статья к решению',
    ]);

    Review::factory()->completed()->create([
        'article_id' => $article->id,
    ]);

    $this->actingAs($editor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Требуется решение по рукописи');
});

test('chief editor sees assign editor task for unassigned submission', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    Article::factory()->submitted()->create([
        'editor_id' => null,
        'title' => 'Неназначенная подача',
    ]);

    $this->actingAs($eic)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Назначьте редактора секции');
});

test('chief editor sees publish task for approved article', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    Article::factory()->approved()->create([
        'title' => 'Готовая к публикации',
    ]);

    $this->actingAs($eic)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Статья готова к публикации');
});

test('inbox shows unread visible discussion to author and hides read or resolved', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->submitted()->create([
        'submitted_by' => $author->id,
    ]);

    $unread = Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $editor->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Вопрос редакции по статье',
    ]);

    Discussion::factory()->resolved()->create([
        'article_id' => $article->id,
        'user_id' => $editor->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Решённый вопрос редакции',
    ]);

    $this->actingAs($author)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Новые сообщения в обсуждении')
        ->assertSee('Вопрос редакции по статье')
        ->assertDontSee('Решённый вопрос редакции');

    $unread->readBy($author);

    $this->actingAs($author)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Новые сообщения в обсуждении');
});

test('foreign user does not see discussion of others article', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $foreign = User::factory()->create();
    $foreign->assignRole('author');

    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->submitted()->create([
        'submitted_by' => $author->id,
    ]);

    Discussion::factory()->create([
        'article_id' => $article->id,
        'user_id' => $editor->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Сообщение, скрытое от посторонних',
    ]);

    $this->actingAs($foreign)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Сообщение, скрытое от посторонних');
});

test('navigation shows inbox counter badge with open task count', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($reviewer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('inbox-count');
});

test('editor sees reassign task when reviewer declined or is overdue', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $declinedArticle = Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
        'title' => 'С отказавшим рецензентом',
    ]);

    Review::factory()->inProgress()->create(['article_id' => $declinedArticle->id]);
    Review::factory()->declined()->create(['article_id' => $declinedArticle->id]);

    $overdueArticle = Article::factory()->inReview()->create([
        'editor_id' => $editor->id,
        'title' => 'С просроченным рецензентом',
    ]);

    Review::factory()->overdue()->create(['article_id' => $overdueArticle->id]);

    $this->actingAs($editor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Рецензент отказался — переназначьте')
        ->assertSee('Рецензент просрочил дедлайн — переназначьте')
        ->assertSeeInOrder(['Рецензент просрочил дедлайн', 'Рецензент отказался']);
});

test('dashboard shows empty inbox state and hides counter for roleless user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Всё сделано. Задач, требующих внимания, нет.')
        ->assertDontSee('inbox-count');
});

test('inbox result is memoized per user within a request', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    Review::factory()->withDeadlines()->create([
        'reviewer_id' => $reviewer->id,
        'status' => ReviewStatus::Pending,
    ]);

    expect(DashboardInbox::for($reviewer))
        ->toBe(DashboardInbox::for($reviewer))
        ->and(DashboardInbox::countFor($reviewer))
        ->toBe(DashboardInbox::for($reviewer)->count());
});

test('countFor matches inbox count for editor with mixed workload', function () {
    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    Article::factory()->submitted()->create([
        'editor_id' => $editor->id,
    ]);

    Article::factory()->accepted()->create([
        'editor_id' => $editor->id,
    ]);

    expect(DashboardInbox::countFor($editor))
        ->toBe(DashboardInbox::for($editor)->count());
});
