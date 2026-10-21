<?php

namespace App\Http\Controllers;

use App\Models\Page;

/**
 * PURPOSE: Serves static CMS pages (About, For Authors,
 * Contacts, Join, Peer Review, Publication Ethics, Archiving, Education)
 * by slug lookup.
 */
class PageController extends Controller
{
    public function about()
    {
        $page = Page::where('slug', 'about')->firstOrFail();

        return view('pages.show', compact('page'));
    }

    public function forAuthors()
    {
        $page = Page::where('slug', 'for-authors')->firstOrFail();

        return view('pages.for-authors', compact('page'));
    }

    /**
     * PURPOSE: Renders the education section page listing
     * the journal's educational services.
     */
    public function education()
    {
        $page = Page::where('slug', 'education')->firstOrFail();

        return view('pages.show', compact('page'));
    }

    public function contacts()
    {
        $page = Page::where('slug', 'contacts')->firstOrFail();

        return view('pages.show', compact('page'));
    }

    public function crossmarkPolicy()
    {
        return view('pages.crossmark-policy', [
            'domains' => config('services.crossref.crossmark.domains', []),
        ]);
    }

    public function peerReview()
    {
        $page = Page::where('slug', 'peer-review')->firstOrFail();

        return view('pages.show', compact('page'));
    }

    public function publicationEthics()
    {
        $page = Page::where('slug', 'publication-ethics')->firstOrFail();

        return view('pages.show', compact('page'));
    }

    public function archiving()
    {
        $page = Page::where('slug', 'archiving')->firstOrFail();

        return view('pages.show', compact('page'));
    }
}
