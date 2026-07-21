<?php

namespace App\Http\Controllers;

use App\Models\Event;

/**
 * PURPOSE: Serves upcoming and past event listings with
 * optional type filtering.
 */
class EventController extends Controller
{
    public function index()
    {
        $typeFilter = request('type');

        $upcomingEvents = Event::published()
            ->upcoming()
            ->when($typeFilter, fn ($q, $type) => $q->where('event_type', $type))
            ->orderBy('event_date')
            ->get();

        $pastEvents = Event::published()
            ->where('event_date', '<', now())
            ->when($typeFilter, fn ($q, $type) => $q->where('event_type', $type))
            ->orderByDesc('event_date')
            ->paginate(12)
            ->withQueryString();

        $eventTypes = [
            'conference' => 'Конференции',
            'forum' => 'Форумы',
            'webinar' => 'Вебинары / Семинары',
            'deadline' => 'Дедлайны',
        ];

        return view('events.index', compact('upcomingEvents', 'pastEvents', 'eventTypes'));
    }
}
