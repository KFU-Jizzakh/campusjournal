<?php

namespace App\Http\Controllers;

use App\Models\Page;

/**
 * PURPOSE: Serves static CMS pages (About, For Authors,
 * Contacts, Join) by slug lookup.
 */
class PageController extends Controller
{
    public function about()
    {
        $page = Page::where('slug', 'about')->firstOrFail();

        return view('pages.about', compact('page'));
    }

    public function forAuthors()
    {
        $page = Page::where('slug', 'for-authors')->firstOrFail();

        return view('pages.for-authors', compact('page'));
    }

    public function contacts()
    {
        $page = Page::where('slug', 'contacts')->firstOrFail();

        return view('pages.contacts', compact('page'));
    }

    public function join()
    {
        return view('pages.join');
    }

    public function crossmarkPolicy()
    {
        $config = config('services.crossref.crossmark');

        return view('pages.crossmark-policy', [
            'policyUrl' => $config['policy_url'] ?? '',
            'domains' => $config['domains'] ?? [],
        ]);
    }
}
