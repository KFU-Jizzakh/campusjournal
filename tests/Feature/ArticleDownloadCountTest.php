<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

function createArticleWithPdf(array $attrs = []): Article
{
    $issue = Issue::factory()->create(['status' => 'published']);
    $pdf = UploadedFile::fake()->create('article.pdf', 1024, 'application/pdf');
    $path = $pdf->store('submissions', 'local');

    return Article::factory()->published()->create(array_merge([
        'issue_id' => $issue->id,
        'views_count' => 0,
        'downloads_count' => 0,
        'pdf_path' => $path,
    ], $attrs));
}

it('increments downloads count when downloading with ?download=1', function () {
    $article = createArticleWithPdf();

    $this->get(route('articles.pdf', ['article' => $article, 'download' => 1]));

    expect($article->refresh()->downloads_count)->toBe(1);
});

it('does not increment downloads count without ?download parameter', function () {
    $article = createArticleWithPdf();

    $this->get(route('articles.pdf', $article));

    expect($article->refresh()->downloads_count)->toBe(0);
});

it('does not increment downloads count for authenticated-only articles', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    $pdf = UploadedFile::fake()->create('article.pdf', 1024, 'application/pdf');
    $path = $pdf->store('submissions', 'local');

    $article = Article::factory()->create([
        'issue_id' => $issue->id,
        'downloads_count' => 0,
        'pdf_path' => $path,
    ]);

    $this->get(route('articles.pdf', ['article' => $article, 'download' => 1]));

    expect($article->refresh()->downloads_count)->toBe(0);
});

it('deduplicates downloads within same session', function () {
    $article = createArticleWithPdf();

    $this->get(route('articles.pdf', ['article' => $article, 'download' => 1]));
    $this->get(route('articles.pdf', ['article' => $article, 'download' => 1]));
    $this->get(route('articles.pdf', ['article' => $article, 'download' => 1]));

    expect($article->refresh()->downloads_count)->toBe(1);
});

it('increments downloads count for retracted articles', function () {
    $article = createArticleWithPdf(['status' => ArticleStatus::Retracted]);

    $this->get(route('articles.pdf', ['article' => $article, 'download' => 1]));

    expect($article->refresh()->downloads_count)->toBe(1);
});

it('does not increment downloads count when authenticated user downloads non-published article', function () {
    $issue = Issue::factory()->create(['status' => 'published']);
    $pdf = UploadedFile::fake()->create('article.pdf', 1024, 'application/pdf');
    $path = $pdf->store('submissions', 'local');

    $user = User::factory()->create();
    $article = Article::factory()->create([
        'issue_id' => $issue->id,
        'downloads_count' => 0,
        'pdf_path' => $path,
        'submitted_by' => $user->id,
        'status' => ArticleStatus::Submitted,
    ]);

    $this->actingAs($user)
        ->get(route('articles.pdf', ['article' => $article, 'download' => 1]));

    expect($article->refresh()->downloads_count)->toBe(0);
});

it('increments downloads count for different articles independently', function () {
    $article1 = createArticleWithPdf();
    $article2 = createArticleWithPdf();

    $this->get(route('articles.pdf', ['article' => $article1, 'download' => 1]));
    $this->get(route('articles.pdf', ['article' => $article2, 'download' => 1]));

    expect($article1->refresh()->downloads_count)->toBe(1);
    expect($article2->refresh()->downloads_count)->toBe(1);
});
