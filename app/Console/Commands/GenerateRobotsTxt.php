<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * PURPOSE: Generates public/robots.txt referencing the sitemap
 * URL derived from APP_URL.
 *
 * SPECIFICATION: SPEC-11/AC-3
 */
class GenerateRobotsTxt extends Command
{
    protected $signature = 'robots:generate';

    protected $description = 'Generate public/robots.txt based on APP_URL';

    public function handle(): int
    {
        $appUrl = config('app.url');
        $sitemapUrl = rtrim($appUrl, '/').'/sitemap.xml';

        $content = <<<TXT
User-agent: *
Disallow: /admin/
Disallow: /dashboard/
Disallow: /profile/
Allow: /

Sitemap: {$sitemapUrl}
TXT;

        File::put(public_path('robots.txt'), $content);

        $this->info('robots.txt generated at '.public_path('robots.txt'));
        $this->info('Sitemap URL: '.$sitemapUrl);

        return self::SUCCESS;
    }
}
