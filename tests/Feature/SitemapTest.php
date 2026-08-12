<?php

declare(strict_types=1);

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Issue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    File::delete(public_path('sitemap.xml'));
});

afterEach(function () {
    File::delete(public_path('sitemap.xml'));
});

it('generates sitemap xml via command', function () {
    $issue = Issue::factory()->create(['status' => 'published', 'published_at' => now()]);
    $article = Article::factory()->published()->create(['issue_id' => $issue->id]);
    $author = Author::factory()->create();
    $author->articles()->attach($article->id, ['order' => 1]);

    Artisan::call('sitemap:generate');

    expect(File::exists(public_path('sitemap.xml')))->toBeTrue();

    $content = File::get(public_path('sitemap.xml'));

    expect($content)
        ->toContain(url('/education'))
        ->toContain(url('/articles/'.$article->id))
        ->toContain(url('/issues/'.$issue->id))
        ->toContain(url('/authors/'.$author->id));
});

it('fallback route returns empty sitemap when file is missing', function () {
    File::delete(public_path('sitemap.xml'));
    expect(File::exists(public_path('sitemap.xml')))->toBeFalse();

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    expect($response->getContent())
        ->toContain('<urlset')
        ->not->toContain('<url>');

    expect(File::exists(public_path('sitemap.xml')))->toBeFalse();
});

it('does not include unpublished articles', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::Draft]);

    Artisan::call('sitemap:generate');
    $content = File::get(public_path('sitemap.xml'));

    expect($content)->not->toContain(url('/articles/'.$article->id));
});
