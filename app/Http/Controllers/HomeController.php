<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organization;

/**
 * PURPOSE: Serves the public homepage with upcoming events
 * and partner organisations. Planned issues are intentionally
 * not shown (SPEC-24/AC-3).
 *
 * SPECIFICATION: SPEC-24/AC-3
 */
class HomeController extends Controller
{
    public function index()
    {
        $events = Event::published()
            ->upcoming()
            ->orderBy('event_date')
            ->take(3)
            ->get();

        $organizations = Organization::orderBy('sort_order')->get();

        return view('home', compact('events', 'organizations'));
    }
}
