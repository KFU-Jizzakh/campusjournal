@extends('layouts.public')

@section('title', $page->title . ' — Global Campus RU')

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-serif text-primary mb-8">{{ $page->title }}</h1>
            <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                @purify($page->body)
            </div>
            <div class="mt-10 p-6 bg-gray-50 rounded-lg border border-gray-200">
                <h3 class="text-xl font-bold text-primary mb-3">{{ __('pages.for_authors_ready') }}</h3>
                <p class="text-gray-600 mb-4">{{ __('pages.for_authors_description') }}</p>
                @auth
                    <a href="{{ route('submissions.create') }}" class="inline-block bg-accent hover:bg-accent-light text-white px-6 py-3 rounded font-semibold transition">{{ __('pages.for_authors_submit') }}</a>
                @else
                    <a href="{{ route('register') }}" class="inline-block bg-accent hover:bg-accent-light text-white px-6 py-3 rounded font-semibold transition">{{ __('pages.for_authors_register') }}</a>
                @endauth
            </div>
        </div>
    </section>
@endsection
