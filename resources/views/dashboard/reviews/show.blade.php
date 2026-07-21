<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.reviewing_heading') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-3">{{ __('dashboard.manuscript') }}</h3>
            <h4 class="text-lg text-gray-900">{{ $review->article->title }}</h4>
            @if($review->article->category)
                <p class="text-sm text-gray-500 mt-1">{{ __('dashboard.section_col') }}: {{ $review->article->category->name }}</p>
            @endif
            @if($review->article->abstract_ru)
                <div class="mt-4 bg-gray-50 rounded-lg p-4">
                    <h5 class="text-xs font-medium text-gray-400 uppercase mb-2">{{ __('common.abstract') }}</h5>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $review->article->abstract_ru }}</p>
                </div>
            @endif
            @if($review->article->pdf_path || $review->article->blinded_pdf_path)
                @if($review->article->needsBlindedPdf())
                    {{-- Double-blind without blinded PDF — link won't show, this is a safety check --}}
                @elseif($review->article->isDoubleBlind() && $review->article->blinded_pdf_path)
                    <span class="inline-flex items-center gap-1 mt-3 text-xs px-2 py-0.5 rounded-full bg-yellow-50 text-yellow-700">Анонимизированная версия</span>
                    <br>
                    <a href="{{ route('articles.blinded-pdf', $review->article) }}" target="_blank" class="inline-block text-sm text-primary hover:underline">{{ __('dashboard.download_manuscript') }}</a>
                @else
                    <a href="{{ route('articles.pdf', $review->article) }}" target="_blank" class="inline-block mt-3 text-sm text-primary hover:underline">{{ __('dashboard.download_manuscript') }}</a>
                @endif
            @endif

            @if($review->review_due_at)
                <div class="mt-4 p-3 rounded-lg bg-{{ $review->deadlineCssClass() }}-50 border border-{{ $review->deadlineCssClass() }}-100">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-{{ $review->deadlineCssClass() }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-{{ $review->deadlineCssClass() }}-700">
                            {{ $review->deadlineLabel() }}
                        </span>
                    </div>
                </div>
            @endif
        </div>

        @if($review->isInProgress())
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.review_heading') }}</h3>

            <form method="POST" action="{{ route('reviews.update', $review) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="recommendation" :value="__('dashboard.recommendation')" />
                    <select id="recommendation" name="recommendation" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>
                        <option value="">{{ __('dashboard.select_recommendation') }}</option>
                        <option value="accept" {{ old('recommendation', $review->recommendation) === 'accept' ? 'selected' : '' }}>{{ __('dashboard.decision_accept') }}</option>
                        <option value="minor_revision" {{ old('recommendation', $review->recommendation) === 'minor_revision' ? 'selected' : '' }}>{{ __('article.recommendation_minor') }}</option>
                        <option value="major_revision" {{ old('recommendation', $review->recommendation) === 'major_revision' ? 'selected' : '' }}>{{ __('article.recommendation_major') }}</option>
                        <option value="reject" {{ old('recommendation', $review->recommendation) === 'reject' ? 'selected' : '' }}>{{ __('article.recommendation_reject') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('recommendation')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="comments_for_author" :value="__('dashboard.comments_for_author')" />
                    <textarea id="comments_for_author" name="comments_for_author" rows="5" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>{{ old('comments_for_author', $review->comments_for_author) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">{{ __('dashboard.comments_for_author_hint') }}</p>
                    <x-input-error :messages="$errors->get('comments_for_author')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="comments_for_editor" :value="__('dashboard.comments_for_editor')" />
                    <textarea id="comments_for_editor" name="comments_for_editor" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>{{ old('comments_for_editor', $review->comments_for_editor) }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">{{ __('dashboard.comments_for_editor_hint') }}</p>
                    <x-input-error :messages="$errors->get('comments_for_editor')" class="mt-1" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="{{ route('reviews.index') }}" class="text-sm text-gray-400 hover:text-gray-600">{{ __('common.cancel') }}</a>
                    <x-primary-button>{{ __('dashboard.submit_review') }}</x-primary-button>
                </div>
            </form>
        </div>
        @elseif($review->isPending())
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <p class="text-gray-600">{{ __('dashboard.accept_first') }}</p>
            <div class="flex items-center gap-4 mt-4">
                <form method="POST" action="{{ route('reviews.accept', $review) }}" class="inline">
                    @csrf
                    <x-primary-button>{{ __('dashboard.accept_request') }}</x-primary-button>
                </form>
                <form method="POST" action="{{ route('reviews.decline', $review) }}" class="inline" onsubmit="return confirm('{{ __('dashboard.confirm_decline') }}');">
                    @csrf
                    <x-danger-button>{{ __('dashboard.decline_request') }}</x-danger-button>
                </form>
            </div>
        </div>
        @elseif($review->isCompleted())
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <p class="text-green-600 font-medium">{{ __('dashboard.review_sent') }}</p>
        </div>
        @elseif($review->isDeclined())
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <p class="text-red-600 font-medium">{{ __('dashboard.review_declined') }}</p>
        </div>
        @endif

        {{-- Discussion with editor --}}
        @if($review->isPending() || $review->isInProgress())
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Обсуждение с редактором</h3>

                @php
                    $threads = $review->discussions()->root()->with('replies.user.profile', 'user.profile')->latest()->get();
                @endphp

                @if($threads->isNotEmpty())
                    <div class="space-y-4 mb-4">
                        @foreach($threads as $thread)
                            <div class="border border-gray-100 rounded-lg p-3 {{ $thread->is_resolved ? 'bg-gray-50 opacity-60' : '' }}">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-medium text-gray-900">{{ $thread->user->full_name }}</span>
                                    <span class="text-xs text-gray-400">{{ $thread->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                <p class="text-sm text-gray-700">{{ $thread->message }}</p>

                                @if($thread->replies->isNotEmpty())
                                    <div class="mt-2 ml-3 space-y-2 border-l-2 border-gray-100 pl-3">
                                        @foreach($thread->replies as $reply)
                                            <div>
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="text-xs font-medium text-gray-900">{{ $reply->user->full_name }}</span>
                                                    <span class="text-xs text-gray-400">{{ $reply->created_at->format('d.m.Y H:i') }}</span>
                                                </div>
                                                <p class="text-sm text-gray-600">{{ $reply->message }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('submissions.discussions.store', $review->article) }}" class="flex items-start gap-2">
                    @csrf
                    <input type="hidden" name="scope" value="editorial">
                    <input type="hidden" name="review_id" value="{{ $review->id }}">
                    <textarea name="message" rows="2" required maxlength="5000"
                        placeholder="Сообщение редактору..."
                        class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm"></textarea>
                    <x-primary-button class="text-xs px-3 py-1">Отправить</x-primary-button>
                </form>
            </div>
        @endif
    </div>
</x-app-layout>
