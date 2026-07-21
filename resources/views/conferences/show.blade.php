@extends('layouts.public')

@section('title', $conference->title . ' — Global Campus RU')

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('conferences.index') }}" class="text-sm text-primary hover:underline">&larr; {{ __('pages.conferences_back') }}</a>
            </div>

            <h1 class="text-3xl font-bold font-serif text-primary mb-4">{{ $conference->title }}</h1>

            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-8">
                @if($conference->event_date)
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>
                            {{ $conference->event_date->format('d.m.Y') }}
                            @if($conference->event_end_date && $conference->event_end_date != $conference->event_date)
                                — {{ $conference->event_end_date->format('d.m.Y') }}
                            @endif
                        </span>
                    </div>
                @endif
                @if($conference->location)
                    <div class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>{{ $conference->location }}</span>
                    </div>
                @endif
                @if($conference->url)
                    <a href="{{ $conference->url }}" target="_blank" rel="noopener" class="flex items-center gap-1 text-primary hover:underline">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        <span>{{ __('pages.conferences_site') }}</span>
                    </a>
                @endif
            </div>

            @if($conference->description)
                <div class="text-lg text-gray-700 mb-8 leading-relaxed">
                    {{ $conference->description }}
                </div>
            @endif

            @if($conference->body)
                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                    @purify($conference->body)
                </div>
            @endif
        </div>
    </section>
@endsection
