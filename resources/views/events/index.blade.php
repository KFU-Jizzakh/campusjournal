@extends('layouts.public')

@section('title', __('pages.events_title'))

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-serif text-primary mb-6">{{ __('pages.events_heading') }}</h1>

            {{-- Category filter --}}
            <div class="flex flex-wrap gap-2 mb-8">
                <a href="{{ route('events.index') }}"
                   class="text-sm px-3 py-1.5 rounded transition {{ !request('type') ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ __('pages.events_all') }}
                    </a>
                @foreach($eventTypes as $key => $label)
                    <a href="{{ route('events.index', ['type' => $key]) }}"
                       class="text-sm px-3 py-1.5 rounded transition {{ request('type') === $key ? 'bg-primary text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if($upcomingEvents->isNotEmpty())
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('pages.events_upcoming') }}</h2>
                <div class="space-y-4 mb-12">
                    @foreach($upcomingEvents as $event)
                    <div class="flex items-start gap-4 bg-white border border-gray-200 rounded-lg p-6">
                        <div class="shrink-0 text-center bg-primary text-white rounded-lg p-3 min-w-[70px]">
                            <div class="text-2xl font-bold">{{ $event->event_date?->format('d') }}</div>
                            <div class="text-xs uppercase">{{ $event->event_date?->translatedFormat('M Y') }}</div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-900">{{ $event->title }}</h3>
                                <a href="{{ route('events.index', ['type' => $event->event_type]) }}" class="text-xs px-2 py-0.5 rounded bg-primary/10 text-primary font-medium hover:bg-primary/20 transition">
                                    {{ $eventTypes[$event->event_type] ?? $event->event_type }}
                                </a>
                            </div>
                            @if($event->location)
                                <p class="text-sm text-gray-500 mt-1">{{ $event->location }}</p>
                            @endif
                            @if($event->event_end_date && $event->event_end_date != $event->event_date)
                                <p class="text-sm text-gray-500">{{ $event->event_date?->format('d.m.Y') }} — {{ $event->event_end_date->format('d.m.Y') }}</p>
                            @endif
                            @if($event->description)
                                <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ $event->description }}</p>
                            @endif
                            @if($event->url)
                                <a href="{{ $event->url }}" target="_blank" rel="noopener" class="inline-block mt-2 text-sm text-primary font-semibold hover:underline">{{ __('site.details') }}</a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif

            @if($pastEvents->isNotEmpty())
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('pages.events_past') }}</h2>
                <div class="space-y-4">
                    @foreach($pastEvents as $event)
                    <div class="flex items-start gap-4 bg-gray-50 border border-gray-200 rounded-lg p-6 opacity-75">
                        <div class="shrink-0 text-center bg-gray-400 text-white rounded-lg p-3 min-w-[70px]">
                            <div class="text-2xl font-bold">{{ $event->event_date?->format('d') }}</div>
                            <div class="text-xs uppercase">{{ $event->event_date?->translatedFormat('M Y') }}</div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-gray-700">{{ $event->title }}</h3>
                                <a href="{{ route('events.index', ['type' => $event->event_type]) }}" class="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-600 font-medium hover:bg-gray-300 transition">
                                    {{ $eventTypes[$event->event_type] ?? $event->event_type }}
                                </a>
                            </div>
                            @if($event->location)
                                <p class="text-sm text-gray-500 mt-1">{{ $event->location }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $pastEvents->links() }}
                </div>
            @endif

            @if($upcomingEvents->isEmpty() && $pastEvents->isEmpty())
                <p class="text-gray-600">{{ __('pages.events_empty') }}</p>
            @endif
        </div>
    </section>
@endsection
