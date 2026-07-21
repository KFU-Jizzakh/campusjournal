@extends('layouts.public')

@section('title', __('pages.conferences_title'))

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-serif text-primary mb-8">{{ __('pages.conferences_heading') }}</h1>

            @if($upcomingConferences->isNotEmpty())
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('pages.conferences_upcoming') }}</h2>
                <div class="space-y-4 mb-12">
                    @foreach($upcomingConferences as $conference)
                    <a href="{{ route('conferences.show', $conference) }}" class="flex items-start gap-4 bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition block">
                        <div class="shrink-0 text-center bg-primary text-white rounded-lg p-3 min-w-[70px]">
                            <div class="text-2xl font-bold">{{ $conference->event_date?->format('d') }}</div>
                            <div class="text-xs uppercase">{{ $conference->event_date?->translatedFormat('M Y') }}</div>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">{{ $conference->title }}</h3>
                            @if($conference->location)
                                <p class="text-sm text-gray-500 mt-1">{{ $conference->location }}</p>
                            @endif
                            @if($conference->event_end_date && $conference->event_end_date != $conference->event_date)
                                <p class="text-sm text-gray-500">{{ $conference->event_date?->format('d.m.Y') }} — {{ $conference->event_end_date->format('d.m.Y') }}</p>
                            @endif
                            @if($conference->description)
                                <p class="text-sm text-gray-600 mt-2 leading-relaxed">{{ $conference->description }}</p>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif

            @if($pastConferences->isNotEmpty())
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ __('pages.conferences_past') }}</h2>
                <div class="space-y-4">
                    @foreach($pastConferences as $conference)
                    <a href="{{ route('conferences.show', $conference) }}" class="flex items-start gap-4 bg-gray-50 border border-gray-200 rounded-lg p-6 opacity-75 hover:opacity-100 transition block">
                        <div class="shrink-0 text-center bg-gray-400 text-white rounded-lg p-3 min-w-[70px]">
                            <div class="text-2xl font-bold">{{ $conference->event_date?->format('d') }}</div>
                            <div class="text-xs uppercase">{{ $conference->event_date?->translatedFormat('M Y') }}</div>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-700">{{ $conference->title }}</h3>
                            @if($conference->location)
                                <p class="text-sm text-gray-500 mt-1">{{ $conference->location }}</p>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $pastConferences->links() }}
                </div>
            @endif

            @if($upcomingConferences->isEmpty() && $pastConferences->isEmpty())
                <p class="text-gray-600">{{ __('pages.conferences_empty') }}</p>
            @endif
        </div>
    </section>
@endsection
