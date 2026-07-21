@extends('layouts.public')

@section('title', __('pages.issues_title'))

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-serif text-primary mb-8">{{ __('pages.issues_heading') }}</h1>

            @if($issues->isEmpty())
                <p class="text-gray-600">{{ __('pages.issues_empty') }}</p>
            @else
                <div class="grid md:grid-cols-2 gap-6">
                    @foreach($issues as $issue)
                    <article class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                        @if($issue->cover_path)
                            <a href="{{ route('issues.show', $issue) }}">
                                <img src="{{ Storage::url($issue->cover_path) }}" alt="{{ $issue->title }}" class="w-full h-48 object-cover">
                            </a>
                        @endif
                        <div class="p-6">
                            <div class="mb-3">
                                <span class="text-sm font-semibold text-accent">Том {{ $issue->volume }}, №{{ $issue->number }} ({{ $issue->year }})</span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-3">{{ $issue->title }}</h2>
                            @if($issue->theme)
                                <p class="text-sm text-primary font-medium mb-2">Тема: {{ $issue->theme }}</p>
                            @endif
                            <p class="text-gray-600 text-sm leading-relaxed">{{ Str::limit($issue->description, 200) }}</p>
                            <div class="mt-4 flex items-center gap-4">
                                <a href="{{ route('issues.show', $issue) }}" class="text-sm text-primary font-semibold hover:underline">{{ __('site.details') }}</a>
                                @if($issue->pdf_path)
                                    <a href="{{ Storage::url($issue->pdf_path) }}" class="text-sm text-accent font-semibold hover:underline" target="_blank">{{ __('common.download_pdf') }}</a>
                                @endif
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $issues->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
