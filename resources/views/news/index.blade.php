@extends('layouts.public')

@section('title', __('pages.news_title'))

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl lg:text-4xl font-bold font-serif text-primary mb-10 text-center">{{ __('pages.news_heading') }}</h1>

            @if($news->isEmpty())
                <p class="text-center text-gray-500">{{ __('pages.news_empty') }}</p>
            @else
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($news as $item)
                        <article class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-md transition flex flex-col">
                            <div class="p-6 flex flex-col flex-1">
                                <time class="text-sm text-gray-500">{{ $item->published_at?->format('d.m.Y') }}</time>
                                <h2 class="font-bold text-lg text-gray-900 mt-2 mb-3 leading-snug">
                                    <a href="{{ route('news.show', $item) }}" class="hover:text-primary transition">{{ $item->title }}</a>
                                </h2>
                                <p class="text-sm text-gray-600 leading-relaxed flex-1">{{ Str::limit(strip_tags($item->body), 200) }}</p>
                                <a href="{{ route('news.show', $item) }}" class="inline-block mt-4 text-sm text-primary font-semibold hover:underline">{{ __('site.read_more') }}</a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $news->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
