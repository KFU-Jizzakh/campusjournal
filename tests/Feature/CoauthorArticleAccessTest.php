<?php

use App\Enums\ArticleStatus;
use App\Enums\DiscussionScope;
use App\Models\Article;
use App\Models\Author;
use App\Models\Discussion;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeCoauthorOn(Article $article): User
{
    $coauthor = User::factory()->create();
    $article->authors()->attach(Author::factory()->create(['user_id' => $coauthor->id])->id);

    return $coauthor;
}

test('coauthor can open submission page of non-draft article', function () {
    $article = Article::factory()->submitted()->create([
        'title' => 'Статья, доступная соавтору',
    ]);

    $coauthor = makeCoauthorOn($article);

    $this->actingAs($coauthor)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertSee('Статья, доступная соавтору');
});

test('coauthor sees decision and author-facing reviews after decision', function () {
    $article = Article::factory()->create([
        'status' => ArticleStatus::Accepted,
        'submitted_at' => now()->subDays(20),
        'decided_at' => now()->subDays(5),
        'decision' => 'accept',
    ]);

    Review::factory()->completed()->create([
        'article_id' => $article->id,
        'recommendation' => 'accept',
        'comments_for_author' => 'Отличная работа, рекомендую к публикации.',
    ]);

    $coauthor = makeCoauthorOn($article);

    $this->actingAs($coauthor)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertSee('Отличная работа, рекомендую к публикации.');
});

test('coauthor gets forbidden for draft article', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    $coauthor = makeCoauthorOn($article);

    $this->actingAs($coauthor)
        ->get(route('submissions.show', $article))
        ->assertForbidden();
});

test('unrelated user still gets forbidden on submission page', function () {
    $article = Article::factory()->submitted()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('submissions.show', $article))
        ->assertForbidden();
});

test('coauthor can download manuscript pdf but not of a draft', function () {
    $article = Article::factory()->submitted()->create();

    $coauthor = makeCoauthorOn($article);

    Storage::fake($disk = $article->pdf_disk);
    Storage::disk($disk)->put($article->pdf_path, 'pdf-content');

    $this->actingAs($coauthor)
        ->get(route('articles.pdf', $article))
        ->assertOk();

    $draft = Article::factory()->create(['status' => ArticleStatus::Draft]);
    $draftCoauthor = makeCoauthorOn($draft);
    Storage::fake($draftDisk = $draft->pdf_disk);
    Storage::disk($draftDisk)->put($draft->pdf_path, 'pdf-content');

    $this->actingAs($draftCoauthor)
        ->get(route('articles.pdf', $draft))
        ->assertNotFound();
});

test('coauthor sees article-scope discussions but foreign user does not', function () {
    $article = Article::factory()->submitted()->create();

    $coauthor = makeCoauthorOn($article);

    Discussion::factory()->create([
        'article_id' => $article->id,
        'scope' => DiscussionScope::Article,
        'message' => 'Замечание, адресованное всем авторам',
    ]);

    $this->actingAs($coauthor)
        ->get(route('submissions.show', $article))
        ->assertOk()
        ->assertSee('Замечание, адресованное всем авторам');

    $this->actingAs(User::factory()->create())
        ->get(route('submissions.show', $article))
        ->assertForbidden();
});

test('dashboard coauthorship rows link to submission page', function () {
    $article = Article::factory()->submitted()->create([
        'title' => 'Кликабельная соавторская статья',
    ]);

    $coauthor = makeCoauthorOn($article);

    $this->actingAs($coauthor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('submissions.show', $article));
});
