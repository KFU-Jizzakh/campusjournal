@extends('layouts.public')

@section('title', $news->title . ' — Global Campus RU')

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('news.index') }}" class="text-sm text-primary hover:underline">&larr; {{ __('pages.news_back') }}</a>
            </div>

            <article>
                <time class="text-sm text-gray-500">{{ $news->published_at?->format('d.m.Y') }}</time>
                <h1 class="text-3xl font-bold font-serif text-primary mt-2 mb-8 leading-tight">{{ $news->title }}</h1>
                <div class="prose prose-lg max-w-none text-gray-800 leading-relaxed">
                    @purify($news->body)
                </div>
            </article>
        </div>
    </section>
@endsection
