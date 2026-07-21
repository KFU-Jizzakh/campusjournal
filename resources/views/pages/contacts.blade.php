@extends('layouts.public')

@section('title', $page->title . ' — Global Campus RU')

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-serif text-primary mb-8">{{ $page->title }}</h1>
            <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                @purify($page->body)
            </div>
        </div>
    </section>
@endsection
