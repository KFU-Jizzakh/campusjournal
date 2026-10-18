<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Organization;

/**
 * PURPOSE: Serves the public homepage with upcoming events
 * and partner organisations. Planned issues are intentionally
 * not shown.
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
