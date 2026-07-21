@extends('layouts.public')

@section('title', __('pages.home_title'))

@section('content')
    {{-- Hero --}}
    <section class="bg-primary text-white py-16 lg:py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-3xl lg:text-5xl font-bold font-serif mb-6">{{ config('app.name') }}</h1>
            <p class="text-lg lg:text-xl text-gray-200 max-w-3xl mx-auto leading-relaxed">
                {{ config('journal.tagline') }}
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('for-authors') }}" class="inline-block bg-accent hover:bg-accent-light text-white px-8 py-3 rounded font-semibold transition">{{ __('site.submit_article') }}</a>
                <a href="{{ route('about') }}" class="inline-block border border-white hover:bg-white hover:text-primary px-8 py-3 rounded font-semibold transition">{{ __('nav.about') }}</a>
            </div>
        </div>
    </section>

    {{-- About brief --}}
    <section class="py-12 lg:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="text-4xl font-bold text-primary mb-2">4</div>
                    <div class="text-gray-600">{{ config('journal.frequency') }}</div>
                </div>
                <div class="text-center p-6">
                    <div class="text-4xl font-bold text-primary mb-2">RU/EN</div>
                    <div class="text-gray-600">{{ config('journal.language') }}</div>
                </div>
                <div class="text-center p-6">
                    <div class="text-4xl font-bold text-primary mb-2">Open Access</div>
                    <div class="text-gray-600">{{ config('journal.access') }}</div>
                </div>
            </div>
        </div>
    </section>

    {{-- Planned Issues --}}
    @if($plannedIssues->isNotEmpty())
    <section class="py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl lg:text-3xl font-bold font-serif text-primary mb-8 text-center">{{ __('site.thematic_issues') }}</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($plannedIssues as $issue)
                <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition">
                    <div class="text-sm text-accent font-semibold mb-2">
                        @if($issue->number && $issue->year)
                            №{{ $issue->number }} ({{ $issue->year }})
                        @endif
                    </div>
                    <h3 class="font-bold text-lg text-gray-900 mb-3">{{ $issue->title }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ Str::limit($issue->description, 150) }}</p>
                    <a href="{{ route('issues.show', $issue) }}" class="inline-block mt-4 text-sm text-primary font-semibold hover:underline">{{ __('site.details') }}</a>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- News --}}
    @if($news->isNotEmpty())
    <section class="py-12 lg:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl lg:text-3xl font-bold font-serif text-primary mb-8 text-center">{{ __('nav.news') }}</h2>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($news as $item)
                <article class="bg-white rounded-lg p-6 border border-gray-200 hover:shadow-md transition flex flex-col">
                    <time class="text-sm text-gray-500">{{ $item->published_at?->format('d.m.Y') }}</time>
                    <h3 class="font-bold text-lg text-gray-900 mt-2 mb-3 leading-snug flex-1">
                        <a href="{{ route('news.show', $item) }}" class="hover:text-primary transition">{{ $item->title }}</a>
                    </h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ Str::limit(strip_tags($item->body), 150) }}</p>
                    <a href="{{ route('news.show', $item) }}" class="inline-block mt-4 text-sm text-primary font-semibold hover:underline">{{ __('site.read_more') }}</a>
                </article>
                @endforeach
            </div>
            <div class="text-center mt-8">
                <a href="{{ route('news.index') }}" class="text-primary font-semibold hover:underline">{{ __('site.all_news') }}</a>
            </div>
        </div>
    </section>
    @endif

    {{-- Events --}}
    @if($events->isNotEmpty())
    <section class="py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl lg:text-3xl font-bold font-serif text-primary mb-8 text-center">{{ __('pages.events_upcoming') }}</h2>
            <div class="space-y-4 max-w-3xl mx-auto">
                @foreach($events as $event)
                <div class="flex items-start gap-4 bg-white border border-gray-200 rounded-lg p-6">
                    <div class="shrink-0 text-center bg-primary text-white rounded-lg p-3 min-w-[70px]">
                        <div class="text-2xl font-bold">{{ $event->event_date?->format('d') }}</div>
                        <div class="text-xs uppercase">{{ $event->event_date?->translatedFormat('M Y') }}</div>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $event->title }}</h3>
                        @if($event->location)
                            <p class="text-sm text-gray-500 mt-1">{{ $event->location }}</p>
                        @endif
                        <p class="text-sm text-gray-600 mt-2">{{ Str::limit($event->description, 200) }}</p>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="text-center mt-6">
                <a href="{{ route('events.index') }}" class="text-primary font-semibold hover:underline">{{ __('site.all_events') }}</a>
            </div>
        </div>
    </section>
    @endif

    {{-- Organizations --}}
    @if($organizations->isNotEmpty())
    <section class="py-12 lg:py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl lg:text-3xl font-bold font-serif text-primary mb-8 text-center">{{ __('site.educational_orgs') }}</h2>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($organizations as $org)
                <div class="bg-white rounded-lg p-6 border border-gray-200 text-center">
                    @if($org->logo_path)
                        <img src="{{ Storage::url($org->logo_path) }}" alt="{{ $org->name }}" class="h-16 mx-auto mb-4 object-contain">
                    @endif
                    <h3 class="font-bold text-gray-900 mb-3">{{ $org->name }}</h3>
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $org->description }}</p>
                    @if($org->url)
                        <a href="{{ $org->url }}" target="_blank" rel="noopener" class="inline-block mt-3 text-sm text-primary hover:underline">{{ __('site.go_to_site') }}</a>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
@endsection
