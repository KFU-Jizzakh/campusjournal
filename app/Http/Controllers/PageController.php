<?php

namespace App\Http\Controllers;

use App\Models\Page;

/**
 * PURPOSE: Serves static CMS pages (About, For Authors,
 * Contacts, Join, Peer Review, Publication Ethics, Archiving)
 * by slug lookup.
 *
 * SPECIFICATION: SPEC-20
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

    public function contacts()
    {
        $page = Page::where('slug', 'contacts')->firstOrFail();

        return view('pages.show', compact('page'));
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

    /**
     * PURPOSE: Renders the peer review policy page.
     *
     * SPECIFICATION: SPEC-20
     */
    public function peerReview()
    {
        $page = Page::where('slug', 'peer-review')->firstOrFail();

        return view('pages.show', compact('page'));
    }

    /**
     * PURPOSE: Renders the publication ethics policy page.
     *
     * SPECIFICATION: SPEC-20
     */
    public function publicationEthics()
    {
        $page = Page::where('slug', 'publication-ethics')->firstOrFail();

        return view('pages.show', compact('page'));
    }

    /**
     * PURPOSE: Renders the archiving policy page.
     *
     * SPECIFICATION: SPEC-20
     */
    public function archiving()
    {
        $page = Page::where('slug', 'archiving')->firstOrFail();

        return view('pages.show', compact('page'));
    }
}
