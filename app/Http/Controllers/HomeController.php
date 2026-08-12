<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Issue;
use App\Models\Organization;

/**
 * PURPOSE: Serves the public homepage with the latest issue,
 * planned issues, upcoming events, and partner organisations.
 */
class HomeController extends Controller
{
    public function index()
    {
        $latestIssue = Issue::published()
            ->orderByDesc('year')
            ->orderByDesc('number')
            ->first();

        $plannedIssues = Issue::where('status', 'planned')
            ->orderBy('year')
            ->orderBy('number')
            ->take(4)
            ->get();

        $events = Event::published()
            ->upcoming()
            ->orderBy('event_date')
            ->take(3)
            ->get();

        $organizations = Organization::orderBy('sort_order')->get();

        return view('home', compact(
            'latestIssue', 'plannedIssues', 'events', 'organizations'
        ));
    }
}
