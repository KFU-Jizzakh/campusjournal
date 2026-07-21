@extends('layouts.public')

@section('title', __('pages.editorial_board_title'))

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold font-serif text-primary mb-8">{{ __('pages.editorial_board_heading') }}</h1>

            @if($members->isEmpty())
                <p class="text-gray-600">{{ __('pages.editorial_board_empty') }}</p>
            @else
                <div class="space-y-8">
                    @foreach($members as $member)
                    <div class="bg-white border border-gray-200 rounded-lg p-6">
                        <div class="flex items-start gap-4">
                            <div class="shrink-0 w-16 h-16 rounded-full overflow-hidden bg-primary/10 flex items-center justify-center">
                                @if($member->author->photo_path)
                                    <img src="{{ Storage::url($member->author->photo_path) }}"
                                         alt="{{ $member->author->full_name }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <span class="text-xl font-bold text-primary">
                                        {{ mb_substr($member->author->full_name, 0, 1) }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="text-xl font-bold text-gray-900">{{ $member->author->full_name }}</h2>
                                    <span class="text-xs bg-accent/10 text-accent px-2 py-1 rounded font-semibold">{{ $member->role }}</span>
                                </div>
                                @if($member->author->degree || $member->author->position)
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ collect([$member->author->degree, $member->author->position])->filter()->join(', ') }}
                                    </p>
                                @endif
                                @if($member->author->organization)
                                    <p class="text-sm text-gray-500 mt-1">{{ $member->author->organization }}</p>
                                @endif
                                @if($member->author->bio)
                                    <p class="text-sm text-gray-700 mt-3 leading-relaxed">{{ $member->author->bio }}</p>
                                @endif
                                <div class="flex flex-wrap items-center gap-4 mt-3 text-sm">
                                    @if($member->author->email)
                                        <a href="mailto:{{ $member->author->email }}" class="text-primary hover:underline">{{ $member->author->email }}</a>
                                    @endif
                                    @if($member->author->orcid)
                                        <span class="text-gray-500">ORCID: {{ $member->author->orcid }}</span>
                                    @endif
                                    @if($member->author->website)
                                        <a href="{{ $member->author->website }}" target="_blank" rel="noopener" class="text-primary hover:underline">{{ __('common.website') }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
