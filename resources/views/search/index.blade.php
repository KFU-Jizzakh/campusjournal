@extends('layouts.public')

@section('title', $query ? __('pages.search_title_query', ['query' => $query]) : __('pages.search_title'))

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-serif text-primary mb-6">{{ __('pages.search_heading') }}</h1>

            <form action="{{ route('search') }}" method="GET" class="mb-8">
                <div class="flex gap-3">
                    <input type="text" name="q" value="{{ $query }}" placeholder="{{ __('pages.search_placeholder') }}"
                           class="flex-1 border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring-primary text-sm px-4 py-3"
                           autofocus>
                    <button type="submit" class="bg-primary hover:bg-primary-light text-white px-6 py-3 rounded-lg font-semibold transition text-sm">
                        {{ __('pages.search_button') }}
                    </button>
                </div>
            </form>

            @if($query && $articles)
                @if($articles->isNotEmpty())
                    <p class="text-sm text-gray-500 mb-4">{{ __('pages.search_results') }} {{ $articles->total() }}</p>

                    <div class="space-y-6">
                        @foreach($articles as $article)
                        <article class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition">
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                @if($article->category)
                                    <a href="{{ route('articles.index', ['category' => $article->category->id]) }}" class="text-xs bg-primary/10 text-primary px-2 py-1 rounded font-semibold hover:bg-primary/20 transition">{{ $article->category->name }}</a>
                                @endif
                                @if($article->issue)
                                    <span class="text-xs text-gray-500">№{{ $article->issue->number }} ({{ $article->issue->year }})</span>
                                @endif
                                <span class="text-xs text-gray-400 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    {{ $article->views_count }}
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-2">
                                <a href="{{ route('articles.show', $article) }}" class="hover:text-primary">{{ $article->title }}</a>
                            </h2>
                            @if($article->authors->isNotEmpty())
                                <p class="text-sm text-gray-500 mb-3">{{ $article->authors->pluck('full_name')->join(', ') }}</p>
                            @endif
                            @if($article->abstract_ru)
                                <p class="text-gray-600 text-sm leading-relaxed">{{ Str::limit($article->abstract_ru, 300) }}</p>
                            @endif
                            @if($article->keywords)
                                <div class="flex flex-wrap gap-1.5 mt-3">
                                    @foreach($article->keywords as $kw)
                                        <a href="{{ route('articles.index', ['keyword' => $kw]) }}" class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded hover:bg-primary/10 hover:text-primary transition">{{ $kw }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </article>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $articles->links() }}
                    </div>
                @else
                    <p class="text-gray-600">{{ __('pages.search_no_results', ['query' => $query]) }}</p>
                @endif
            @elseif(!$query)
                <p class="text-gray-600">{{ __('pages.search_no_query') }}</p>
            @endif
        </div>
    </section>
@endsection
