<?php

declare(strict_types=1);

use App\Models\Article;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('corrections/notice.pdf', 'correction pdf');
});

function correctionArticle(array $articleAttributes = []): Article
{
    return Article::factory()->published()->create($articleAttributes);
}

it('serves a correction file for a published article to guests', function () {
    $article = correctionArticle();
    $correction = $article->corrections()->create([
        'type' => 'corrigendum',
        'title' => 'Уведомление',
        'description' => 'Исправление.',
        'file_path' => 'corrections/notice.pdf',
        'published_at' => now()->toDateString(),
        'created_by' => $article->submitted_by,
    ]);

    $this->get(route('corrections.file', $correction))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('returns 404 for a correction of an unpublished article', function () {
    $article = Article::factory()->submitted()->create();
    $correction = $article->corrections()->create([
        'type' => 'corrigendum',
        'title' => 'Уведомление',
        'description' => 'Исправление.',
        'file_path' => 'corrections/notice.pdf',
        'published_at' => now()->toDateString(),
        'created_by' => $article->submitted_by,
    ]);

    $this->get(route('corrections.file', $correction))->assertNotFound();
});

it('returns 404 when the correction has no file or the file is missing on disk', function () {
    $article = correctionArticle();

    $withoutFile = $article->corrections()->create([
        'type' => 'erratum',
        'title' => 'Без файла',
        'description' => 'Исправление.',
        'file_path' => null,
        'published_at' => now()->toDateString(),
        'created_by' => $article->submitted_by,
    ]);

    $missing = $article->corrections()->create([
        'type' => 'erratum',
        'title' => 'Файл потерян',
        'description' => 'Исправление.',
        'file_path' => 'corrections/gone.pdf',
        'published_at' => now()->toDateString(),
        'created_by' => $article->submitted_by,
    ]);

    $this->get(route('corrections.file', $withoutFile))->assertNotFound();
    $this->get(route('corrections.file', $missing))->assertNotFound();
});
