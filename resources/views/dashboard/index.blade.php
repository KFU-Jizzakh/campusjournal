<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.heading') }}</h2>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm p-4 rounded-lg">{{ session('success') }}</div>
        @endif

        {{-- Editorial summary --}}
        @if($editorialCounts)
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-4">
            <a href="{{ route('editorial.index', ['status' => 'submitted']) }}" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-primary/30 transition">
                <div class="text-2xl font-bold text-primary">{{ $editorialCounts->new_submissions }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('dashboard.new_submissions') }}</div>
            </a>
            <a href="{{ route('editorial.index', ['status' => 'in_review']) }}" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-yellow-300 transition">
                <div class="text-2xl font-bold text-yellow-600">{{ $editorialCounts->in_review }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('dashboard.in_review') }}</div>
            </a>
            <a href="{{ route('editorial.index', ['status' => 'accepted']) }}" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-green-300 transition">
                <div class="text-2xl font-bold text-green-600">{{ $editorialCounts->accepted }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('dashboard.accepted') }}</div>
            </a>
            <a href="{{ route('editorial.index', ['status' => 'copyediting']) }}" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-indigo-300 transition">
                <div class="text-2xl font-bold text-indigo-600">{{ $editorialCounts->copyediting }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('dashboard.copyediting') }}</div>
            </a>
            <a href="{{ route('editorial.index', ['status' => 'production']) }}" class="bg-white rounded-lg border border-gray-200 p-5 hover:border-purple-300 transition">
                <div class="text-2xl font-bold text-purple-600">{{ $editorialCounts->production }}</div>
                <div class="text-sm text-gray-500 mt-1">{{ __('dashboard.production') }}</div>
            </a>
        </div>
        @endif

        {{-- My articles --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.my_articles') }}</h3>
                <a href="{{ route('submissions.create') }}" class="text-sm text-primary hover:underline">{{ __('dashboard.submit_first') }}</a>
            </div>

            @if($myArticles->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">
                    {{ __('dashboard.no_articles') }}
                    <br><a href="{{ route('submissions.create') }}" class="text-primary hover:underline mt-1 inline-block">{{ __('dashboard.submit_first_article') }}</a>
                </div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase border-b border-gray-50">
                            <th class="px-5 py-3 font-medium">{{ __('dashboard.title_col') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('dashboard.section_col') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('dashboard.status_col') }}</th>
                            <th class="px-5 py-3 font-medium">{{ __('dashboard.date_col') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($myArticles as $article)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-3 font-medium text-gray-900">{{ Str::limit($article->title, 60) }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $article->category?->name }}</td>
                            <td class="px-5 py-3">
                                <x-status-badge :color="$article->status->color()" :label="$article->status->label()" />
                            </td>
                            <td class="px-5 py-3 text-gray-400 text-xs">{{ $article->submitted_at?->format('d.m.Y') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('submissions.show', $article) }}" class="text-primary hover:underline text-sm">{{ __('common.open') }}</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Pending reviews --}}
        @if($myReviews->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.assigned_reviews') }}</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($myReviews as $review)
                <div class="flex items-center justify-between px-5 py-4">
                    <div class="min-w-0 flex-1 mr-4">
                        <div class="font-medium text-gray-900 truncate">{{ $review->article?->title }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ __('dashboard.assigned_at') }} {{ $review->assigned_at?->format('d.m.Y') }}</div>
                    </div>
                    <a href="{{ route('reviews.show', $review) }}" class="shrink-0 text-sm text-primary hover:underline">{{ __('dashboard.review_action') }}</a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
