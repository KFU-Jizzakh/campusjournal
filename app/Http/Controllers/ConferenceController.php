<?php

namespace App\Http\Controllers;

use App\Models\Conference;

/**
 * PURPOSE: Serves upcoming and past conference listings
 * with individual conference detail pages.
 */
class ConferenceController extends Controller
{
    public function index()
    {
        $upcomingConferences = Conference::published()
            ->upcoming()
            ->orderBy('event_date')
            ->get();

        $pastConferences = Conference::published()
            ->where('event_date', '<', now())
            ->orderByDesc('event_date')
            ->paginate(12)
            ->withQueryString();

        return view('conferences.index', compact('upcomingConferences', 'pastConferences'));
    }

    public function show(Conference $conference)
    {
        abort_unless($conference->is_published, 404);

        return view('conferences.show', compact('conference'));
    }
}
