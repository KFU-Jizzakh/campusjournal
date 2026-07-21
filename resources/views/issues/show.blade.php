@extends('layouts.public')

@section('title', $issue->full_title . ' — Global Campus RU')
@section('description', Str::limit(strip_tags($issue->description ?? $issue->title), 160))
@section('og_title', $issue->full_title)
@section('og_description', Str::limit(strip_tags($issue->description ?? $issue->title), 200))
@section('og_type', 'website')

@push('meta')
@php
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'PublicationIssue',
    'name' => $issue->full_title,
    'isPartOf' => [
        '@type' => 'Periodical',
        'name' => config('app.name'),
    ],
];
if ($issue->description) $jsonLd['description'] = Str::limit($issue->description, 300);
if ($issue->published_at) $jsonLd['datePublished'] = $issue->published_at->toIso8601String();
if ($issue->volume) $jsonLd['volumeNumber'] = $issue->volume;
if ($issue->number) $jsonLd['issueNumber'] = $issue->number;
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>
@endpush

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('issues.index') }}" class="text-sm text-primary hover:underline">&larr; {{ __('pages.issues_back') }}</a>
            </div>

            <div class="mb-8 flex flex-col md:flex-row md:items-start gap-6">
                @if($issue->cover_path)
                    <img src="{{ Storage::url($issue->cover_path) }}" alt="{{ $issue->title }}" class="w-48 rounded-lg shadow-md flex-shrink-0">
                @endif
                <div>
                    <span class="text-sm font-semibold text-accent">Том {{ $issue->volume }}, №{{ $issue->number }} ({{ $issue->year }})</span>
                    <h1 class="text-3xl font-bold font-serif text-primary mt-2">{{ $issue->title }}</h1>
                    @if($issue->theme)
                        <p class="text-lg text-gray-600 mt-2">Тема номера: {{ $issue->theme }}</p>
                    @endif
                </div>
            </div>

            @if($issue->description)
                <div class="prose prose-lg max-w-none text-gray-700 mb-8">
                    <p>{{ $issue->description }}</p>
                </div>
            @endif

            @if($issue->status !== 'published')
                <div class="bg-blue-50 border border-blue-200 text-blue-800 rounded-lg p-6 text-center">
                    <p class="font-semibold text-lg mb-1">{{ __('pages.issue_unpublished') }}</p>
                    <p class="text-sm text-blue-600">{{ __('pages.issue_unpublished_hint') }}</p>
                </div>
            @else

            @if($issue->pdf_path)
                <div class="mb-8">
                    <a href="{{ Storage::url($issue->pdf_path) }}" class="inline-block bg-accent hover:bg-accent-light text-white px-6 py-3 rounded font-semibold transition" target="_blank">
                        {{ __('common.download_pdf') }}
                    </a>
                </div>
            @endif

            {{-- Articles in this issue --}}
            @if($issue->articles->isNotEmpty())
                <h2 class="text-2xl font-bold font-serif text-primary mt-12 mb-6">{{ __('pages.issue_contents') }}</h2>
                <div class="space-y-4">
                    @foreach($issue->articles as $article)
                    <article class="border border-gray-200 rounded-lg p-5 hover:shadow transition">
                        @if($article->category)
                            <span class="text-xs text-accent font-semibold uppercase">{{ $article->category->name }}</span>
                        @endif
                        <h3 class="font-bold text-gray-900 mt-1">
                            <a href="{{ route('articles.show', $article) }}" class="hover:text-primary">{{ $article->title }}</a>
                        </h3>
                        @if($article->authors->isNotEmpty())
                            <p class="text-sm text-gray-500 mt-1">{{ $article->authors->pluck('full_name')->join(', ') }}</p>
                        @endif
                        @if($article->abstract_ru)
                            <p class="text-sm text-gray-600 mt-2">{{ Str::limit($article->abstract_ru, 200) }}</p>
                        @endif
                    </article>
                    @endforeach
                </div>
            @else
                <div class="mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-gray-600">{{ __('pages.issue_no_articles') }}</p>
                </div>
            @endif
            @endif
        </div>
    </section>
@endsection
