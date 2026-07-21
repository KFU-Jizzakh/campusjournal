@extends('layouts.public')

@section('title', $agreement->title . ' — Global Campus RU')

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4">
    <div class="mb-6">
        <a href="{{ url()->previous() }}" class="text-sm text-gray-400 hover:text-gray-600">&larr; {{ __('common.back') }}</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $agreement->title }}</h1>
    <p class="text-sm text-gray-400 mb-6">
        {{ __('article.copyright_agreement_version', ['version' => $agreement->version, 'date' => $agreement->published_at?->format('d.m.Y') ?? $agreement->created_at->format('d.m.Y')]) }}
    </p>

    <div class="bg-white rounded-lg border border-gray-200 p-6 prose max-w-none text-gray-700 leading-relaxed">
        {!! $agreement->full_text !!}
    </div>

    <div class="mt-8 text-center">
        <a href="{{ url()->previous() }}" class="text-sm text-primary hover:underline">&larr; {{ __('common.back') }}</a>
    </div>
</div>
@endsection
