<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Author;
use App\Models\Conference;
use App\Models\Issue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

/**
 * PURPOSE: Generates public/sitemap.xml with all public URLs
 * using low-memory cursor() traversal.
 *
 * SPECIFICATION: SPEC-11/AC-1, SPEC-11/AC-2, SPEC-21/AC-7
 */
class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate public/sitemap.xml';

    public function handle(): int
    {
        $urls = collect([
            ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => route('about'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('for-authors'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('education'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('contacts'), 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => route('search'), 'changefreq' => 'monthly', 'priority' => '0.3'],
            ['loc' => route('issues.index'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => route('articles.index'), 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => route('events.index'), 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => route('conferences.index'), 'changefreq' => 'weekly', 'priority' => '0.6'],
            ['loc' => route('oai'), 'changefreq' => 'monthly', 'priority' => '0.4'],
        ]);

        $issues = Issue::published()
            ->cursor()
            ->map(fn (Issue $issue) => [
                'loc' => route('issues.show', $issue),
                'lastmod' => $issue->published_at?->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ]);

        $articles = Article::published()
            ->cursor()
            ->map(fn (Article $article) => [
                'loc' => route('articles.show', $article),
                'lastmod' => $article->published_at?->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.8',
            ]);

        $conferences = Conference::published()
            ->cursor()
            ->map(fn (Conference $conference) => [
                'loc' => route('conferences.show', $conference),
                'lastmod' => $conference->published_at?->toDateString() ?? $conference->updated_at?->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ]);

        $authors = Author::whereHas('articles', fn ($q) => $q->published())
            ->cursor()
            ->map(fn (Author $author) => [
                'loc' => route('authors.show', $author),
                'lastmod' => $author->updated_at?->toDateString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ]);

        $urls = $urls
            ->merge($issues)
            ->merge($articles)
            ->merge($conferences)
            ->merge($authors);

        $xml = View::make('sitemap', ['urls' => $urls])->render();
        File::put(public_path('sitemap.xml'), $xml);

        $this->info('Sitemap generated at '.public_path('sitemap.xml'));

        return self::SUCCESS;
    }
}
