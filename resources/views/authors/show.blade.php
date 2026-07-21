@extends('layouts.public')

@section('title', $author->full_name . ' — Global Campus RU')
@section('og_title', $author->full_name)
@section('og_description', collect([$author->degree, $author->position, $author->organization])->filter()->join(', '))
@section('og_type', 'profile')

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('articles.index') }}" class="text-sm text-primary hover:underline">&larr; {{ __('pages.articles_back') }}</a>
            </div>

            {{-- Author profile card --}}
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
                <div class="flex items-start gap-5">
                    <div class="shrink-0 w-20 h-20 rounded-full overflow-hidden bg-primary/10 flex items-center justify-center">
                        @if($author->photo_path)
                            <img src="{{ Storage::url($author->photo_path) }}"
                                 alt="{{ $author->full_name }}"
                                 class="w-full h-full object-cover">
                        @else
                            <span class="text-2xl font-bold text-primary">
                                {{ mb_substr($author->full_name, 0, 1) }}
                            </span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold font-serif text-primary">{{ $author->full_name }}</h1>
                        @if($author->degree || $author->position)
                            <p class="text-sm text-gray-600 mt-1">
                                {{ collect([$author->degree, $author->position])->filter()->join(', ') }}
                            </p>
                        @endif
                        @if($author->organization)
                            <p class="text-sm text-gray-500 mt-1">{{ $author->organization }}</p>
                        @endif
                        @if($author->bio)
                            <p class="text-gray-700 mt-3 leading-relaxed">{{ $author->bio }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-4 mt-3 text-sm">
                            @if($author->email)
                                <a href="mailto:{{ $author->email }}" class="text-primary hover:underline">{{ $author->email }}</a>
                            @endif
                            @if($author->orcid)
                                <a href="https://orcid.org/{{ $author->orcid }}" target="_blank" rel="noopener" class="text-primary hover:underline">ORCID: {{ $author->orcid }}</a>
                            @endif
                            @if($author->spin_code)
                                <span class="text-gray-500">SPIN: {{ $author->spin_code }}</span>
                            @endif
                            @if($author->author_id_elibrary)
                                <span class="text-gray-500">eLibrary ID: {{ $author->author_id_elibrary }}</span>
                            @endif
                            @if($author->website)
                                <a href="{{ $author->website }}" target="_blank" rel="noopener" class="text-primary hover:underline">{{ __('common.website') }}</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Publications --}}
            @if($articles->isNotEmpty())
                <h2 class="text-2xl font-bold font-serif text-primary mb-4">{{ __('common.publications') }}</h2>
                <div class="space-y-4 mb-8">
                    @foreach($articles as $article)
                        <div class="bg-white border border-gray-200 rounded-lg p-5">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                @if($article->category)
                                    <span class="text-xs bg-primary/10 text-primary px-2 py-1 rounded font-semibold">{{ $article->category->name }}</span>
                                @endif
                                @if($article->issue)
                                    <a href="{{ route('issues.show', $article->issue) }}" class="text-xs text-gray-500 hover:text-primary">
                                        Том {{ $article->issue->volume }}, №{{ $article->issue->number }} ({{ $article->issue->year }})
                                    </a>
                                @endif
                            </div>
                            <a href="{{ route('articles.show', $article) }}" class="text-lg font-bold text-primary hover:underline font-serif">
                                {{ $article->title }}
                            </a>
                            @if($article->authors->isNotEmpty())
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $article->authors->pluck('full_name')->join(', ') }}
                                </p>
                            @endif
                            @if($article->abstract_ru)
                                <p class="text-sm text-gray-600 mt-2">{{ Str::limit($article->abstract_ru, 200) }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Issues --}}
            @if($issues->isNotEmpty())
                <h2 class="text-2xl font-bold font-serif text-primary mb-4">{{ __('common.issues_label') }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($issues as $issue)
                        <a href="{{ route('issues.show', $issue) }}" class="bg-white border border-gray-200 rounded-lg px-4 py-3 hover:shadow-md transition">
                            <span class="text-sm font-semibold text-primary">Том {{ $issue->volume }}, №{{ $issue->number }} ({{ $issue->year }})</span>
                            @if($issue->title)
                                <span class="text-sm text-gray-500 block">{{ $issue->title }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
