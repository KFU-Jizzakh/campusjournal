<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.heading') }}</h2>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm p-4 rounded-lg">{{ session('success') }}</div>
        @endif

        {{-- Inbox: tasks requiring attention --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="flex items-center justify-between p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.inbox.heading') }}</h3>
                @if($inbox->isNotEmpty())
                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-50 text-red-700 font-medium">{{ $inbox->count() }}</span>
                @endif
            </div>
            @if($inbox->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">{{ __('dashboard.inbox.empty') }}</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($inbox as $item)
                        <x-inbox-row :item="$item" />
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Review deadlines (editors): overdue and next 7 days --}}
        @if($showEditorial && $reviewDeadlines->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.deadlines.heading') }}</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($reviewDeadlines as $item)
                    <x-inbox-row :item="$item" />
                @endforeach
            </div>
        </div>
        @endif

        {{-- Watchlist (editors): waiting on reviewers or authors --}}
        @if($showEditorial && $watchlist->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.watch.heading') }}</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($watchlist as $item)
                    <x-inbox-row :item="$item" />
                @endforeach
            </div>
        </div>
        @endif

        {{-- Issue assembly (publishers) --}}
        @if($issueAssembly && ($issueAssembly['issue'] || $issueAssembly['ready']->isNotEmpty()))
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.issue_assembly.heading') }}</h3>
            </div>
            @if($issueAssembly['issue'])
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 text-sm">
                    <span class="text-gray-500">{{ __('dashboard.issue_assembly.current_issue') }}</span>
                    <span class="flex items-center gap-2">
                        <span class="font-medium text-gray-900">{{ $issueAssembly['issue']->full_title }}</span>
                        <x-status-badge color="success" :label="__('dashboard.issue_assembly.articles_count', ['count' => $issueAssembly['issue']->articles_count])" />
                    </span>
                </div>
            @else
                <div class="px-5 py-3 border-b border-gray-50 text-sm text-gray-400">{{ __('dashboard.issue_assembly.no_issue') }}</div>
            @endif
            <div class="p-5">
                <h4 class="text-xs font-medium text-gray-400 uppercase mb-3">{{ __('dashboard.issue_assembly.ready_heading') }}</h4>
                @if($issueAssembly['ready']->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('dashboard.issue_assembly.empty_ready') }}</p>
                @else
                    <div class="divide-y divide-gray-50">
                        @foreach($issueAssembly['ready'] as $article)
                            <div class="flex items-center justify-between gap-3 py-2.5">
                                <div class="min-w-0 flex-1 font-medium text-sm text-gray-900 truncate">{{ $article->title }}</div>
                                <div class="flex items-center gap-3 shrink-0">
                                    <x-status-badge :color="$article->status->color()" :label="$article->status->label()" />
                                    <a href="{{ route('editorial.show', $article) }}" class="text-sm text-primary hover:underline">{{ __('dashboard.inbox.action.open') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
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
            <div class="flex flex-wrap items-center justify-between gap-3 p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.my_articles') }}</h3>
                <div class="flex items-center gap-3">
                    <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('dashboard.search_placeholder') }}"
                               class="w-44 sm:w-56 rounded-md border border-gray-300 px-3 py-1 text-xs focus:border-primary focus:ring-primary">
                        <button type="submit" class="shrink-0 text-xs px-2.5 py-1 rounded-md bg-gray-900 text-white hover:bg-gray-800 transition">{{ __('dashboard.search_button') }}</button>
                        @if($search)
                            <a href="{{ route('dashboard') }}" class="shrink-0 text-xs text-gray-400 hover:text-gray-600">{{ __('dashboard.search_reset') }}</a>
                        @endif
                    </form>
                    <a href="{{ route('submissions.create') }}" class="text-sm text-primary hover:underline">{{ __('dashboard.submit_first') }}</a>
                </div>
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

        {{-- Coauthored articles (read-only) --}}
        @if($coauthoredArticles->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.coauthored_articles') }}</h3>
            </div>
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
                    @foreach($coauthoredArticles as $row)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ Str::limit($row['article']->title, 60) }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $row['article']->category?->name }}</td>
                        <td class="px-5 py-3">
                            <x-status-badge :color="$row['article']->status->color()" :label="$row['article']->status->label()" />
                        </td>
                        <td class="px-5 py-3 text-gray-400 text-xs">{{ $row['article']->submitted_at?->format('d.m.Y') }}</td>
                        <td class="px-5 py-3 text-right">
                            @if($row['url'])
                                <a href="{{ $row['url'] }}" class="text-primary hover:underline text-sm">{{ __('common.open') }}</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

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
