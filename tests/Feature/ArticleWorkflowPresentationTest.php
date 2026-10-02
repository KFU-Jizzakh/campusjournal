<?php

use App\Enums\ArticleStatus;
use App\Enums\ReviewType;
use App\Models\Article;
use App\Models\Author;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('workflow steps mark submitted article at review step', function () {
    $article = Article::factory()->submitted()->create();

    $steps = $article->workflowSteps();

    expect($steps)->toHaveCount(7)
        ->and($steps[0]['state'])->toBe('current')
        ->and($steps[1]['state'])->toBe('pending')
        ->and($steps[6]['state'])->toBe('pending');
});

test('workflow steps show review start date from first assigned review', function () {
    $article = Article::factory()->inReview()->create();

    Review::factory()->create([
        'article_id' => $article->id,
        'assigned_at' => now()->subDays(4),
    ]);

    $steps = $article->workflowSteps();

    expect($steps[1]['date']?->toDateString())->toBe(now()->subDays(4)->toDateString())
        ->and($steps[0]['state'])->toBe('done')
        ->and($steps[1]['state'])->toBe('current')
        ->and($steps[2]['state'])->toBe('pending');
});

test('workflow steps terminate at decision for rejected articles', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Rejected,
        'submitted_at' => now()->subDays(30),
        'decided_at' => now()->subDays(10),
    ]);

    $steps = $article->workflowSteps();

    expect($steps[2]['state'])->toBe('rejected')
        ->and($steps[2]['label'])->toBe('Отклонена')
        ->and($steps[3]['state'])->toBe('cancelled');
});

test('workflow steps are empty for drafts and withdrawn articles', function () {
    expect(Article::factory()->create(['status' => ArticleStatus::Draft])->workflowSteps())->toBe([])
        ->and(Article::factory()->create(['status' => ArticleStatus::Withdrawn])->workflowSteps())->toBe([]);
});

test('workflow steps mark decision as done for revision articles', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Revision,
        'submitted_at' => now()->subDays(30),
        'decided_at' => now()->subDays(10),
    ]);

    $steps = $article->workflowSteps();

    expect(collect($steps)->pluck('state')->all())
        ->toBe(['done', 'done', 'done', 'pending', 'pending', 'pending', 'pending'])
        ->and(collect($steps)->where('state', 'current'))->toBeEmpty()
        ->and($steps[2]['date']?->toDateString())->toBe(now()->subDays(10)->toDateString());
});

test('production checklist reflects article readiness', function () {
    $article = Article::factory()->inReview()->create([
        'review_type' => ReviewType::DoubleBlind,
    ]);

    $checklist = collect($article->productionChecklist())->keyBy(fn (array $item) => $item['label']);

    $blinded = $checklist['Слепой PDF'];
    $reviews = $checklist['Завершённые рецензии (0)'];
    $decision = $checklist['Редакционное решение'];

    expect($blinded['done'])->toBeFalse()
        ->and($blinded['skipped'])->toBeFalse()
        ->and($reviews['done'])->toBeFalse()
        ->and($decision['done'])->toBeFalse();

    $article->update(['blinded_pdf_path' => 'blinded/test.pdf']);

    $blinded = collect($article->productionChecklist())->firstWhere('label', 'Слепой PDF');

    expect($blinded['done'])->toBeTrue();
});

test('production checklist skips blinded pdf for open review type', function () {
    $article = Article::factory()->submitted()->create([
        'review_type' => ReviewType::Open,
    ]);

    $blinded = collect($article->productionChecklist())->firstWhere('label', 'Слепой PDF');

    expect($blinded['done'])->toBeTrue()
        ->and($blinded['skipped'])->toBeTrue();
});

test('article page renders timeline for author and checklist for editor', function () {
    $author = User::factory()->create();
    $author->assignRole('author');

    $editor = User::factory()->create();
    $editor->assignRole('section-editor');

    $article = Article::factory()->submitted()->create([
        'submitted_by' => $author->id,
        'editor_id' => $editor->id,
        'title' => 'Статья с таймлайном',
    ]);

    $this->actingAs($author)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertSee('Путь статьи')
        ->assertSee('Рецензирование');

    $this->actingAs($editor)
        ->get(route('editorial.show', $article))
        ->assertOk()
        ->assertSee('Путь статьи')
        ->assertSee('Чеклист производства')
        ->assertSee('Слепой PDF');
});

test('editorial index searches by title fragment', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    Article::factory()->submitted()->create(['title' => 'Методы машинного обучения']);
    Article::factory()->submitted()->create(['title' => 'История физики']);

    $this->actingAs($eic)
        ->get(route('editorial.index', ['q' => 'машинного']))
        ->assertOk()
        ->assertSee('Методы машинного обучения')
        ->assertDontSee('История физики');
});

test('editorial index searches by author name and combines with status filter', function () {
    $eic = User::factory()->create();
    $eic->assignRole('editor-in-chief');

    $matching = Article::factory()->submitted()->create(['title' => 'Подходящая статья']);
    $matching->authors()->attach(Author::factory()->create(['full_name' => 'Галимов Рустем']));

    $other = Article::factory()->inReview()->create(['title' => 'Чужая статья']);
    $other->authors()->attach(Author::factory()->create(['full_name' => 'Иванова Анна']));

    $this->actingAs($eic)
        ->get(route('editorial.index', ['q' => 'Галимов']))
        ->assertOk()
        ->assertSee('Подходящая статья')
        ->assertDontSee('Чужая статья');

    $this->actingAs($eic)
        ->get(route('editorial.index', ['q' => 'Галимов', 'status' => 'in_review']))
        ->assertOk()
        ->assertDontSee('Подходящая статья');
});
