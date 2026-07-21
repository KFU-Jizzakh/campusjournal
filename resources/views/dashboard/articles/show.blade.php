<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ Str::limit($article->title, 60) }}</h2>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm p-4 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-4 rounded-lg">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <x-status-badge :color="$article->status->color()" :label="$article->status->label()" />
                @if($article->isEditable())
                    <a href="{{ route('submissions.edit', $article) }}" class="text-sm text-primary hover:underline">{{ __('common.edit') }}</a>
                @endif
            </div>

            <h3 class="text-lg font-semibold text-gray-900 mb-3">{{ $article->title }}</h3>

            @if($article->category)
                <p class="text-sm text-gray-500 mb-4">{{ __('dashboard.section_col') }}: {{ $article->category->name }}</p>
            @endif

            @if($article->abstract_ru)
                <div class="mb-4">
                    <h4 class="text-xs font-medium text-gray-400 uppercase mb-1">{{ __('common.abstract') }}</h4>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $article->abstract_ru }}</p>
                </div>
            @endif

            @if($article->abstract_en)
                <div class="mb-4">
                    <h4 class="text-xs font-medium text-gray-400 uppercase mb-1">{{ __('common.abstract_en') }}</h4>
                    <p class="text-sm text-gray-700 leading-relaxed italic">{{ $article->abstract_en }}</p>
                </div>
            @endif

            @if($article->authors->isNotEmpty())
                <div class="mb-4">
                    <h4 class="text-xs font-medium text-gray-400 uppercase mb-1">{{ __('article.coauthors_label') }}</h4>
                    @foreach($article->authors as $author)
                        <div class="text-sm text-gray-700">
                            <span class="font-medium">{{ $author->full_name }}</span>
                            @if($author->degree || $author->position)
                                — {{ collect([$author->degree, $author->position])->filter()->join(', ') }}
                            @endif
                            @if($author->organization)
                                <span class="text-gray-400">({{ $author->organization }})</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="text-xs text-gray-400">{{ __('article.submitted_at') }} {{ $article->submitted_at?->format('d.m.Y H:i') }}</div>
        </div>

        {{-- Copyright Agreement --}}
        @if($article->latestAgreement && $article->latestAgreement->agreement)
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-3">{{ __('article.copyright_agreement_heading') }}</h3>
            <p class="text-sm text-gray-500 mb-2">
                {{ __('article.copyright_agreement_accepted_at') }} {{ $article->latestAgreement->created_at->format('d.m.Y H:i') }}
            </p>
            <a href="{{ route('agreements.show', $article->latestAgreement->agreement) }}" target="_blank" class="text-sm text-primary hover:underline">
                {{ __('article.copyright_agreement_full_text') }} ({{ __('article.copyright_agreement_version', ['version' => $article->latestAgreement->agreement->version, 'date' => $article->latestAgreement->agreement->published_at?->format('d.m.Y') ?? $article->latestAgreement->agreement->created_at->format('d.m.Y')]) }})
            </a>
        </div>
        @endif

        {{-- Supplementary Files --}}
        @if($article->files->isNotEmpty() || $article->isEditable())
        <div class="bg-white rounded-lg border border-gray-200 p-6" x-data="{ showUploadForm: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Дополнительные файлы</h3>
                @if($article->isEditable())
                    <button @click="showUploadForm = !showUploadForm" class="text-sm text-primary hover:underline">
                        <span x-show="!showUploadForm">{{ __('common.add_file') }}</span>
                        <span x-show="showUploadForm" x-cloak>{{ __('common.cancel') }}</span>
                    </button>
                @endif
            </div>

            {{-- Upload Form --}}
            @if($article->isEditable())
            <div x-show="showUploadForm" x-cloak class="mb-6 p-4 bg-gray-50 rounded-lg">
                <form action="{{ route('article-files.store', $article) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.file') }}</label>
                        <div class="relative">
                            <input type="file" name="file" id="file" required class="hidden" onchange="document.getElementById('file-name').textContent = this.files[0]?.name || '{{ __('common.no_file') }}'">
                            <label for="file" class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:bg-primary-light text-white text-sm font-medium rounded cursor-pointer transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                {{ __('common.choose_file') }}
                            </label>
                            <span id="file-name" class="ml-3 text-sm text-gray-600">{{ __('common.no_file') }}</span>
                        </div>
                        @error('file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">{{ __('common.max_size', ['size' => '100 МБ']) }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="file_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.file_type') }}</label>
                            <select name="file_type" id="file_type" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">
                                @foreach(\App\Enums\ArticleFileType::cases() as $type)
                                    <option value="{{ $type->value }}" {{ old('file_type') == $type->value ? 'selected' : '' }}>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('file_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="visibility" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.access_level') }}</label>
                            <select name="visibility" id="visibility" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">
                                @foreach(\App\Enums\ArticleFileVisibility::cases() as $visibility)
                                    <option value="{{ $visibility->value }}" {{ old('visibility') == $visibility->value ? 'selected' : '' }}>
                                        {{ $visibility->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('visibility')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="license" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.license') }}</label>
                            <select name="license" id="license" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">
                                <option value="">{{ __('common.select_license') }}</option>
                                @foreach(\App\Enums\ArticleFileLicense::cases() as $license)
                                    <option value="{{ $license->value }}" {{ old('license') == $license->value ? 'selected' : '' }}>
                                        {{ $license->label() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('license')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="language" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.language') }}</label>
                            <select name="language" id="language" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 text-sm">
                                <option value="">{{ __('common.select_language') }}</option>
                                <option value="ru" {{ old('language') == 'ru' ? 'selected' : '' }}>{{ __('common.russian') }}</option>
                                <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>{{ __('common.english') }}</option>
                                <option value="de" {{ old('language') == 'de' ? 'selected' : '' }}>{{ __('common.german') }}</option>
                                <option value="fr" {{ old('language') == 'fr' ? 'selected' : '' }}>{{ __('common.french') }}</option>
                                <option value="es" {{ old('language') == 'es' ? 'selected' : '' }}>{{ __('common.spanish') }}</option>
                                <option value="zh" {{ old('language') == 'zh' ? 'selected' : '' }}>{{ __('common.chinese') }}</option>
                            </select>
                            @error('language')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-primary hover:bg-primary-light text-white px-4 py-2 rounded text-sm font-medium transition">
                            {{ __('common.upload_file') }}
                        </button>
                    </div>
                </form>
            </div>
            @endif

            {{-- Files List --}}
            @if($article->files->isNotEmpty())
                <div class="space-y-3">
                    @foreach($article->files as $file)
                        <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                            {{-- File Icon/Preview --}}
                            <div class="flex-shrink-0">
                                @if($file->isImage() && $file->thumbnail_url)
                                    <img src="{{ $file->thumbnail_url }}" alt="{{ $file->original_name }}" class="w-16 h-16 object-cover rounded">
                                @else
                                    <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @switch($file->file_type->value)
                                                @case('research_data')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                                    @break
                                                @case('image')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    @break
                                                @case('video')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    @break
                                                @case('audio')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                                    @break
                                                @case('code')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                                    @break
                                                @default
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            @endswitch
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            {{-- File Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $file->original_name }}</p>
                                    <span class="text-xs px-2 py-0.5 rounded-full {{ $file->visibility->badgeClass() }}">
                                        {{ $file->visibility->label() }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $file->file_type->label() }} • {{ $file->formatted_size }}
                                    @if($file->language)
                                        • {{ strtoupper($file->language) }}
                                    @endif
                                </p>
                                @if($file->license)
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        Лицензия: {{ $file->license->label() }}
                                    </p>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="flex items-center gap-2">
                                <a href="{{ route('article-files.download', $file) }}" 
                                   class="text-gray-400 hover:text-primary transition"
                                    title="{{ __('common.download') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                                @if($article->isEditable())
                                    <form action="{{ route('article-files.destroy', $file) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="{{ __('common.delete') }}">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-4">Нет дополнительных файлов</p>
            @endif
        </div>
        @endif

        {{-- Editor decision --}}
        @if($article->decision_comments)
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-3">{{ __('dashboard.decision_label') }}</h3>
            <div class="flex items-center gap-2 mb-3">
                <span class="text-xs px-2 py-0.5 rounded-full
                    @switch($article->decision)
                        @case('accept') bg-green-50 text-green-700 @break
                        @case('revision') bg-orange-50 text-orange-700 @break
                        @case('reject') bg-red-50 text-red-700 @break
                    @endswitch
                ">
                    @switch($article->decision)
                        @case('accept') Принята @break
                        @case('revision') На доработку @break
                        @case('reject') Отклонена @break
                    @endswitch
                </span>
                <span class="text-xs text-gray-400">{{ $article->decided_at?->format('d.m.Y H:i') }}</span>
            </div>
            <p class="text-sm text-gray-700">{{ $article->decision_comments }}</p>
        </div>
        @endif

        {{-- Galley Proof — Author Approval --}}
        @if($article->isAwaitingApproval())
            <div class="bg-white rounded-lg border border-blue-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('article.galley_heading') }}</h3>
                <p class="text-sm text-gray-600 mb-3">
                    Свёрстанная версия вашей статьи готова к проверке. Пожалуйста, проверьте гранки и утвердите публикацию или запросите правки.
                </p>
                @if($article->galley_pdf_path)
                    <div class="mb-4">
                        <a href="{{ route('articles.galley-pdf', $article) }}" target="_blank"
                            class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 border border-blue-200 rounded-lg px-4 py-2 bg-blue-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            {{ __('article.galley_download') }}
                        </a>
                    </div>
                @endif

                <div class="flex items-start gap-3">
                    <form method="POST" action="{{ route('submissions.approve-galley', $article) }}">
                        @csrf
                        <x-primary-button class="bg-green-600 hover:bg-green-700 text-sm px-4 py-2">
                            {{ __('article.galley_approve_button') }}
                        </x-primary-button>
                    </form>

                    <form method="POST" action="{{ route('submissions.request-revision', $article) }}" class="flex-1">
                        @csrf
                        <div class="space-y-2">
                            <textarea name="comment" rows="3" required maxlength="5000"
                                placeholder="{{ __('article.galley_revision_comment_hint') }}"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm"></textarea>
                            <x-input-error :messages="$errors->get('comment')" class="mt-1" />
                            <x-primary-button class="bg-yellow-500 hover:bg-yellow-600 text-sm px-4 py-2">
                                {{ __('article.galley_request_revision_button') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- Galley Approved --}}
        @if($article->isApproved())
            <div class="bg-white rounded-lg border border-green-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">{{ __('article.galley_heading') }}</h3>
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-sm px-2 py-0.5 rounded-full bg-green-50 text-green-700">{{ __('article.galley_approved_info') }}</span>
                    <span class="text-xs text-gray-400">{{ $article->galley_approved_at?->format('d.m.Y H:i') }}</span>
                </div>
                @if($article->galley_pdf_path)
                    <a href="{{ route('articles.galley-pdf', $article) }}" target="_blank"
                        class="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 border border-blue-200 rounded-lg px-4 py-2 bg-blue-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        {{ __('article.galley_download') }}
                    </a>
                @endif
            </div>
        @endif

        {{-- Reviews — visible to author only after editorial decision --}}
        @if($article->decision && $article->completedReviews()->exists())
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.reviews_heading') }}</h3>
            <div class="space-y-4">
                @foreach($article->completedReviews()->get() as $index => $review)
                <div class="border border-gray-100 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900">{{ __('dashboard.review_reviewer') }}{{ $index + 1 }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full
                            @switch($review->recommendation)
                                @case('accept') bg-green-50 text-green-700 @break
                                @case('minor_revision') bg-yellow-50 text-yellow-700 @break
                                @case('major_revision') bg-orange-50 text-orange-700 @break
                                @case('reject') bg-red-50 text-red-700 @break
                            @endswitch
                        ">
                            @switch($review->recommendation)
                                @case('accept') {{ __('article.recommendation_accept') }} @break
                                @case('minor_revision') {{ __('article.recommendation_minor') }} @break
                                @case('major_revision') {{ __('article.recommendation_major') }} @break
                                @case('reject') {{ __('article.recommendation_reject') }} @break
                            @endswitch
                        </span>
                    </div>
                    @if($review->comments_for_author)
                        <p class="text-sm text-gray-700">{{ $review->comments_for_author }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Discussions --}}
        @if(!$article->isDraft() && !$article->isPublished())
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">Обсуждения</h3>

            <form method="POST" action="{{ route('submissions.discussions.store', $article) }}" class="mb-6 p-4 bg-gray-50 rounded-lg">
                @csrf
                <input type="hidden" name="scope" value="article">
                <textarea name="message" rows="2" required maxlength="5000"
                    placeholder="Задать вопрос редактору..."
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm mb-2"></textarea>
                <x-primary-button class="text-xs px-3 py-1">Отправить</x-primary-button>
            </form>

            @php
                $visibleDiscussions = $article->discussions
                    ->where('parent_id', null)
                    ->filter(fn($d) => auth()->user() ? $d->isVisibleTo(auth()->user(), $article) : false)
                    ->sortByDesc('created_at');
            @endphp

            @if($visibleDiscussions->isEmpty())
                <p class="text-sm text-gray-400 text-center py-4">Нет обсуждений</p>
            @else
                <div class="space-y-4">
                    @foreach($visibleDiscussions as $thread)
                        <div class="border border-gray-100 rounded-lg p-4 {{ $thread->is_resolved ? 'bg-gray-50 opacity-60' : '' }}">
                            <div class="flex items-center gap-2 mb-2">
                                @if($thread->wasUnread)
                                    <span class="inline-block w-2 h-2 rounded-full bg-blue-500" title="Новое сообщение"></span>
                                @endif
                                <span class="text-sm font-medium text-gray-900">{{ $thread->user->full_name }}</span>
                                <span class="text-xs text-gray-400">{{ $thread->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $thread->message }}</p>

                            @if($thread->replies->isNotEmpty())
                                <div class="mt-3 ml-4 space-y-2 border-l-2 border-gray-100 pl-4">
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

                            @if(!$thread->is_resolved)
                                <form method="POST" action="{{ route('submissions.discussions.store', $article) }}" class="mt-3 flex items-start gap-2">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $thread->id }}">
                                    <input type="hidden" name="scope" value="{{ $thread->scope->value }}">
                                    <textarea name="message" rows="1" required maxlength="5000"
                                        placeholder="Ответить..."
                                        class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm"></textarea>
                                    <x-primary-button class="text-xs px-2 py-1">Отправить</x-primary-button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        <div class="text-center">
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600">&larr; {{ __('common.back_to_dashboard') }}</a>
        </div>
    </div>
</x-app-layout>
