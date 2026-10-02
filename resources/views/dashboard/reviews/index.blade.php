<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.my_reviews') }}</h2>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm p-4 rounded-lg">{{ session('success') }}</div>
        @endif

        @if(session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-700 text-sm p-4 rounded-lg">{{ session('info') }}</div>
        @endif

        {{-- Reviewer workload summary --}}
        <div class="flex flex-wrap gap-2">
            <x-status-badge color="info" :label="__('dashboard.reviewer_stats.active', ['count' => $stats['active']])" />
            <x-status-badge color="success" :label="__('dashboard.reviewer_stats.completed', ['count' => $stats['completed']])" />
            @if($stats['avg_days'] !== null)
                <x-status-badge color="gray" :label="__('dashboard.reviewer_stats.avg_days', ['count' => $stats['avg_days']])" />
            @else
                <x-status-badge color="gray" :label="__('dashboard.reviewer_stats.avg_days_empty')" />
            @endif
            <x-status-badge color="warning" :label="__('dashboard.reviewer_stats.declines', ['count' => $stats['declines_year']])" />
        </div>

        <div class="bg-white rounded-lg border border-gray-200">
            @if($activeReviews->isEmpty())
                <div class="p-8 text-center text-gray-400 text-sm">{{ __('dashboard.no_reviews') }}</div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($activeReviews as $review)
                    <div class="flex items-center justify-between px-5 py-4">
                        <div class="min-w-0 flex-1 mr-4">
                            <div class="font-medium text-gray-900">{{ $review->article?->title }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ __('dashboard.assigned_at') }} {{ $review->assigned_at?->format('d.m.Y') }}
                                @if($review->review_due_at)
                                    <span class="mx-1">&middot;</span>
                                    @php $deadlineStatus = $review->deadlineStatus(); @endphp
                                    <span class="
                                        @switch($deadlineStatus)
                                            @case('overdue') text-red-600 font-medium @break
                                            @case('urgent') text-orange-600 font-medium @break
                                            @case('warning') text-yellow-600 @break
                                            @case('normal') text-green-600 @break
                                            @default text-gray-500 @break
                                        @endswitch
                                    ">
                                        @if($review->isOverdue())
                                            {{ __('dashboard.deadline_overdue') }}{{ $review->daysOverdue() }} {{ trans_choice('день|дня|дней', $review->daysOverdue()) }}
                                        @else
                                            {{ __('dashboard.deadline') }} {{ $review->review_due_at->format('d.m.Y') }}
                                            ({{ $review->daysUntilReviewDue() }} {{ trans_choice('день|дня|дней', $review->daysUntilReviewDue()) }})
                                        @endif
                                    </span>
                                @endif
                                <span class="mx-1">&middot;</span>
                                <x-status-badge :color="$review->status->color()" :label="$review->status->label()" class="text-xs px-1.5 py-0.5" />
                            </div>
                        </div>
                        @if($review->status === App\Enums\ReviewStatus::Pending)
                            <div class="flex items-center gap-2 shrink-0">
                                <form method="POST" action="{{ route('reviews.accept', $review) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-green-600 hover:text-green-800 font-medium">{{ __('dashboard.accept_request') }}</button>
                                </form>
                                <span class="text-gray-300">|</span>
                                <form method="POST" action="{{ route('reviews.decline', $review) }}" class="inline" onsubmit="return confirm('{{ __('dashboard.confirm_decline') }}');">
                                    @csrf
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">{{ __('dashboard.decline_request') }}</button>
                                </form>
                            </div>
                        @elseif($review->status === App\Enums\ReviewStatus::InProgress)
                            <a href="{{ route('reviews.show', $review) }}" class="shrink-0 text-sm text-primary hover:underline">{{ __('dashboard.review_action') }}</a>
                        @endif
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Completed reviews --}}
        @if($completedReviews->isNotEmpty())
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.completed_reviews') }}</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($completedReviews as $review)
                <div class="flex items-center justify-between px-5 py-4">
                    <div class="min-w-0 flex-1 mr-4">
                        <div class="font-medium text-gray-900 truncate">{{ $review->article?->title }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ __('dashboard.completed_at') }} {{ $review->completed_at?->format('d.m.Y') }}</div>
                    </div>
                    <a href="{{ route('reviews.show', $review) }}" class="shrink-0 text-sm text-primary hover:underline">{{ __('common.open') }}</a>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
