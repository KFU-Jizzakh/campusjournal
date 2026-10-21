<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

/**
 * PURPOSE: Serves the pre-generated public/sitemap.xml produced by the
 * sitemap:generate console command (scheduled daily). Falls back to an
 * empty urlset until the first run. A controller instead of a route
 * closure so route:cache keeps working.
 */
class SitemapController extends Controller
{
    public function __invoke()
    {
        $path = public_path('sitemap.xml');

        if (File::exists($path)) {
            return response(File::get($path), 200, [
                'Content-Type' => 'application/xml',
            ]);
        }

        return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>', 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
