<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.manuscript_number') }}{{ $article->id }}</h2>
    </x-slot>

    <div class="space-y-6">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm p-4 rounded-lg">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-4 rounded-lg">{{ session('error') }}</div>
        @endif

        {{-- Article info --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.article_info') }}</h3>
                <x-status-badge :color="$article->status->color()" :label="$article->status->label()" />
            </div>

            <h4 class="text-lg text-gray-900 mb-3">{{ $article->title }}</h4>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm mb-4">
                <div class="flex gap-2"><dt class="text-gray-400">{{ __('article.main_author') }}:</dt><dd class="text-gray-900">{{ $article->submitter?->full_name }}</dd></div>
                <div class="flex gap-2"><dt class="text-gray-400">{{ __('dashboard.section_col') }}:</dt><dd class="text-gray-900">{{ $article->category?->name ?? '—' }}</dd></div>
                <div class="flex gap-2"><dt class="text-gray-400">{{ __('article.submitted_at') }}</dt><dd class="text-gray-900">{{ $article->submitted_at?->format('d.m.Y H:i') }}</dd></div>
                <div class="flex gap-2"><dt class="text-gray-400">{{ __('article.coauthors_label') }}</dt><dd class="text-gray-900">{{ $article->authors->pluck('full_name')->join(', ') ?: '—' }}</dd></div>
            </dl>

            @if($article->abstract_ru)
                <div class="bg-gray-50 rounded-lg p-4 mb-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase mb-1">{{ __('common.abstract') }}</h5>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $article->abstract_ru }}</p>
                </div>
            @endif

            @if($article->abstract_en)
                <div class="bg-gray-50 rounded-lg p-4 mb-3">
                    <h5 class="text-xs font-medium text-gray-400 uppercase mb-1">{{ __('common.abstract_en') }}</h5>
                    <p class="text-sm text-gray-700 leading-relaxed italic">{{ $article->abstract_en }}</p>
                </div>
            @endif

            @if($article->pdf_path)
                <a href="{{ route('articles.pdf', $article) }}" target="_blank" class="text-sm text-primary hover:underline">{{ __('dashboard.download_manuscript') }}</a>
            @endif
        </div>

        {{-- Funding --}}
        @if(!empty($article->funding))
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">{{ __('article.funding_label') }}</h3>
                <div class="space-y-2">
                    @foreach($article->funding as $funder)
                        <div class="text-sm text-gray-700">
                            <span class="font-medium">{{ $funder['funder_name'] }}</span>
                            @if(!empty($funder['award_number']))
                                — {{ $funder['award_number'] }}
                            @endif
                            @if(!empty($funder['funder_identifier']))
                                <div class="text-xs text-gray-400">{{ $funder['funder_identifier'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Review Type --}}
        @if($article->isSubmitted())
        <div class="bg-white rounded-lg border border-gray-200 p-6" x-data="{ editing: false }">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900">Тип рецензирования</h3>
                @if($article->canChangeReviewType())
                    <button @click="editing = !editing" class="text-sm text-primary hover:underline">
                        <span x-show="!editing">Изменить</span>
                        <span x-show="editing" x-cloak>Отмена</span>
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-2">
                <span class="text-xs px-2 py-0.5 rounded-full {{ $article->review_type->badgeClass() }}">
                    {{ $article->review_type->label() }}
                </span>
                @if(!$article->canChangeReviewType())
                    <span class="text-xs text-gray-400">Тип рецензирования нельзя изменить после назначения рецензентов</span>
                @endif
            </div>

            <form x-show="editing" x-cloak method="POST" action="{{ route('editorial.set-review-type', $article) }}" class="mt-4 space-y-3">
                @csrf
                @method('PUT')
                <div class="space-y-2">
                    @foreach(App\Enums\ReviewType::cases() as $type)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="review_type" value="{{ $type->value }}"
                                {{ $article->review_type === $type ? 'checked' : '' }}
                                class="text-primary focus:ring-primary">
                            {{ $type->label() }}
                        </label>
                    @endforeach
                </div>
                <x-primary-button>Сохранить</x-primary-button>
            </form>
        </div>
        @endif

        {{-- Blinded PDF --}}
        @if($article->isDoubleBlind())
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-3">Анонимизированная рукопись</h3>

            @if($article->blinded_pdf_path)
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                    <span class="text-sm text-green-700">Загружена {{ $article->blinded_at?->format('d.m.Y H:i') }}</span>
                </div>
                <div class="flex items-center gap-3 mb-4">
                    <a href="{{ route('articles.blinded-pdf', $article) }}" target="_blank" class="text-sm text-primary hover:underline">Скачать анонимизированную версию</a>
                </div>

                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('editorial.upload-blinded-pdf', $article) }}" enctype="multipart/form-data" class="flex items-center gap-3">
                        @csrf
                        <input type="file" name="blinded_pdf" accept=".pdf" class="text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                        <button type="submit" class="text-sm text-primary hover:underline">Заменить</button>
                    </form>

                    @if($article->canChangeReviewType())
                        <form method="POST" action="{{ route('editorial.delete-blinded-pdf', $article) }}" onsubmit="return confirm('Удалить анонимизированную рукопись?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-500 hover:underline">Удалить</button>
                        </form>
                    @endif
                </div>
            @else
                <div class="flex items-center gap-2 mb-4">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span class="text-sm text-red-600">Не загружена</span>
                </div>
                <form method="POST" action="{{ route('editorial.upload-blinded-pdf', $article) }}" enctype="multipart/form-data" class="flex items-center gap-3">
                    @csrf
                    <input type="file" name="blinded_pdf" accept=".pdf" required class="text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-primary file:text-white hover:file:bg-primary-light">
                    <x-primary-button>Загрузить</x-primary-button>
                </form>
            @endif
        </div>
        @endif

        {{-- Supplementary Files --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6" x-data="{ showUploadForm: false }">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Дополнительные файлы</h3>
                <button @click="showUploadForm = !showUploadForm" class="text-sm text-primary hover:underline">
                    <span x-show="!showUploadForm">{{ __('common.add_file') }}</span>
                    <span x-show="showUploadForm" x-cloak>{{ __('common.cancel') }}</span>
                </button>
            </div>

            {{-- Upload Form --}}
            <div x-show="showUploadForm" x-cloak class="mb-6 p-4 bg-gray-50 rounded-lg">
                <form action="{{ route('article-files.store', $article) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    <div>
                        <label for="file" class="block text-sm font-medium text-gray-700 mb-1">{{ __('common.file') }}</label>
                        <input type="file" name="file" id="file" required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-light">
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
                                    <option value="{{ $visibility->value }}" {{ old('visibility', 'editorial_only') == $visibility->value ? 'selected' : '' }}>
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
                                <div class="flex items-center gap-2 flex-wrap">
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
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Загружен: {{ $file->uploader?->full_name }} • {{ $file->created_at->format('d.m.Y H:i') }}
                                </p>
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
                                <form action="{{ route('article-files.destroy', $file) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600 transition" title="{{ __('common.delete') }}">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 text-center py-4">{{ __('common.no_file') }}</p>
            @endif
        </div>

        {{-- Assign editor --}}
        @if($showAssignEditor ?? false)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">{{ __('dashboard.section_editor') }}</h3>
                <form method="POST" action="{{ route('editorial.assign-editor', $article) }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="editor_id" :value="__('dashboard.assign_editor')" />
                        <select id="editor_id" name="editor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>
                            <option value="">{{ __('dashboard.select_editor') }}</option>
                            @foreach($sectionEditors as $editor)
                                <option value="{{ $editor->id }}">{{ $editor->full_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('editor_id')" class="mt-1" />
                    </div>
                    <x-primary-button>{{ __('dashboard.assign_button') }}</x-primary-button>
                </form>
            </div>
        @elseif($article->editor)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">{{ __('dashboard.section_editor') }}</h3>
                <p class="text-sm text-gray-900">{{ $article->editor->full_name }}</p>
            </div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">{{ __('dashboard.section_editor') }}</h3>
                <p class="text-sm text-gray-400">{{ __('dashboard.not_assigned') }}</p>
            </div>
        @endif

        {{-- Reviews --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.reviews_heading') }}</h3>

            @if($article->reviews->isNotEmpty())
                <div class="space-y-3 mb-5">
                    @foreach($article->reviews as $review)
                    <div class="border border-gray-100 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-900">{{ $review->reviewer?->full_name }}</span>
                            <div class="flex items-center gap-2">
                                @if($review->recommendation)
                                    <span class="text-xs px-1.5 py-0.5 rounded-full {{ $review->recommendationBadgeClass() }}">
                                        {{ $review->recommendationLabel() }}
                                    </span>
                                @endif
                                <x-status-badge :color="$review->status->color()" :label="$review->status->label()" class="text-xs px-1.5 py-0.5" />
                            </div>
                        </div>
                        @if($review->isCompleted())
                            @if($review->comments_for_author)
                                <div class="mt-2 text-sm">
                                    <span class="text-xs text-gray-400">{{ __('dashboard.for_author') }}</span>
                                    <p class="text-gray-700 mt-0.5">{{ $review->comments_for_author }}</p>
                                </div>
                            @endif
                            @if($review->comments_for_editor)
                                <div class="mt-2 text-sm">
                                    <span class="text-xs text-gray-400">{{ __('dashboard.for_editor_conf') }}</span>
                                    <p class="text-gray-700 mt-0.5 bg-amber-50 p-2 rounded text-sm">{{ $review->comments_for_editor }}</p>
                                </div>
                            @endif
                            <div class="text-xs text-gray-400 mt-2">{{ __('dashboard.completed_at') }} {{ $review->completed_at?->format('d.m.Y H:i') }}</div>
                        @endif
                        <div class="text-xs text-gray-400 mt-1">{{ __('dashboard.assigned_at') }} {{ $review->assigned_at?->format('d.m.Y H:i') }}</div>
                    </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 mb-4">{{ __('dashboard.no_reviews_yet') }}</p>
            @endif

            @if($article->isReviewable())
                <form method="POST" action="{{ route('editorial.assign-reviewer', $article) }}" class="flex items-end gap-3 border-t border-gray-100 pt-4">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="reviewer_id" :value="__('dashboard.assign_reviewer')" />
                        <select id="reviewer_id" name="reviewer_id" {{ $article->needsBlindedPdf() ? 'disabled' : '' }} class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" {{ $article->needsBlindedPdf() ? '' : 'required' }}>
                            <option value="">{{ __('dashboard.select_reviewer') }}</option>
                            @foreach($reviewers as $reviewer)
                                <option value="{{ $reviewer->id }}">{{ $reviewer->full_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('reviewer_id')" class="mt-1" />
                    </div>
                    @if($article->needsBlindedPdf())
                        <div class="text-sm text-red-600">{{ __('article.error_missing_blinded_pdf') }}</div>
                    @else
                        <x-primary-button>{{ __('dashboard.assign_button') }}</x-primary-button>
                    @endif
                </form>
            @endif
        </div>

        {{-- Decision --}}
        @if($article->decision)
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
                            @case('accept') {{ __('article.recommendation_accept') }} @break
                            @case('revision') На доработку @break
                            @case('reject') {{ __('article.recommendation_reject') }} @break
                        @endswitch
                    </span>
                    <span class="text-xs text-gray-400">{{ $article->decidedBy?->full_name }}, {{ $article->decided_at?->format('d.m.Y H:i') }}</span>
                </div>
                <p class="text-sm text-gray-700">{{ $article->decision_comments }}</p>
            </div>
        @elseif($article->canBeDecided())
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.make_decision') }}</h3>
                <form method="POST" action="{{ route('editorial.decide', $article) }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="decision" :value="__('dashboard.decision_label')" />
                        <select id="decision" name="decision" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>
                            <option value="">{{ __('dashboard.select_decision') }}</option>
                            <option value="accept" {{ old('decision') === 'accept' ? 'selected' : '' }}>{{ __('dashboard.decision_accept') }}</option>
                            <option value="revision" {{ old('decision') === 'revision' ? 'selected' : '' }}>{{ __('dashboard.decision_revision') }}</option>
                            <option value="reject" {{ old('decision') === 'reject' ? 'selected' : '' }}>{{ __('dashboard.decision_reject') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('decision')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="decision_comments" :value="__('dashboard.decision_comment')" />
                        <textarea id="decision_comments" name="decision_comments" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>{{ old('decision_comments') }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">{{ __('dashboard.decision_comment_hint') }}</p>
                        <x-input-error :messages="$errors->get('decision_comments')" class="mt-1" />
                    </div>
                    <x-primary-button>{{ __('dashboard.confirm_decision') }}</x-primary-button>
                </form>
            </div>
        @endif

        {{-- Send to Copyediting --}}
        @if($article->isAccepted())
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.copyediting_heading') }}</h3>
                <p class="text-sm text-gray-500 mb-3">{{ __('dashboard.copyediting_hint') }}</p>
                <form method="POST" action="{{ route('editorial.send-to-copyediting', $article) }}">
                    @csrf
                    <x-primary-button>{{ __('dashboard.send_to_copyediting') }}</x-primary-button>
                </form>
            </div>
        @endif

        {{-- Copyediting Workspace --}}
        @if($article->isCopyediting())
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.copyediting_heading') }}</h3>
                <p class="text-sm text-gray-500 mb-3">
                    {{ __('dashboard.copyediting_label') }} {{ $article->copyeditedBy?->full_name }}, {{ $article->copyedited_at?->format('d.m.Y H:i') }}
                </p>

                {{-- Copyedited File --}}
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">{{ __('dashboard.copyedited_file_heading') }}</h4>

                    @if($article->copyedited_file_path)
                        <div class="flex items-center gap-4 mb-3">
                            <div class="text-sm text-gray-600">
                                <a href="{{ route('editorial.download-copyedited-file', $article) }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    {{ basename($article->copyedited_file_path) }}
                                </a>
                            </div>
                            <span class="text-xs text-gray-400">
                                {{ __('dashboard.copyedited_file_uploaded_by') }}: {{ $article->copyeditedFileUploadedBy?->full_name }},
                                {{ $article->copyedited_file_uploaded_at?->format('d.m.Y H:i') }}
                            </span>
                        </div>

                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ route('editorial.upload-copyedited-file', $article) }}" enctype="multipart/form-data" class="inline">
                                @csrf
                                <div class="flex items-center gap-2">
                                    <input type="file" name="copyedited_file" accept="application/pdf,.docx" required
                                        class="text-sm border border-gray-300 rounded-md file:mr-3 file:py-1.5 file:px-3 file:text-sm file:font-medium file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                                    <x-primary-button class="text-xs px-3 py-1.5">{{ __('dashboard.copyedited_file_replace') }}</x-primary-button>
                                </div>
                                <x-input-error :messages="$errors->get('copyedited_file')" class="mt-1" />
                            </form>

                            <form method="POST" action="{{ route('editorial.delete-copyedited-file', $article) }}" class="inline" onsubmit="return confirm('{{ __('dashboard.copyedited_file_delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <x-danger-button class="text-xs px-3 py-1.5">{{ __('dashboard.copyedited_file_delete') }}</x-danger-button>
                            </form>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 mb-3">{{ __('dashboard.copyedited_file_status') }}</p>
                        <form method="POST" action="{{ route('editorial.upload-copyedited-file', $article) }}" enctype="multipart/form-data">
                            @csrf
                            <p class="text-sm text-gray-500 mb-3">{{ __('dashboard.copyedited_file_upload_hint') }}</p>
                            <div class="flex items-center gap-2">
                                <input type="file" name="copyedited_file" accept="application/pdf,.docx" required
                                    class="text-sm border border-gray-300 rounded-md file:mr-3 file:py-1.5 file:px-3 file:text-sm file:font-medium file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                                <x-primary-button class="text-xs px-3 py-1.5">{{ __('dashboard.copyedited_file_upload_button') }}</x-primary-button>
                            </div>
                            <x-input-error :messages="$errors->get('copyedited_file')" class="mt-1" />
                        </form>
                    @endif
                </div>

                {{-- Send to Production --}}
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <form method="POST" action="{{ route('editorial.send-to-production', $article) }}">
                        @csrf
                        <x-primary-button>{{ __('dashboard.send_to_production') }}</x-primary-button>
                    </form>
                </div>
            </div>
        @endif

        {{-- Copyedited File (post-copyediting, read-only) --}}
        @if(!$article->isCopyediting() && $article->copyedited_file_path)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.copyedited_file_heading') }}</h3>
                <div class="flex items-center gap-4">
                    <a href="{{ route('editorial.download-copyedited-file', $article) }}" target="_blank"
                        class="text-sm text-blue-600 hover:text-blue-800">
                        {{ basename($article->copyedited_file_path) }}
                    </a>
                    <span class="text-xs text-gray-400">
                        {{ __('dashboard.copyedited_file_uploaded_by') }}:
                        {{ $article->copyeditedFileUploadedBy?->full_name }},
                        {{ $article->copyedited_file_uploaded_at?->format('d.m.Y H:i') }}
                    </span>
                </div>
            </div>
        @endif

        {{-- Galley Proofs --}}
        @if($showGalleyUpload ?? false)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.galley_heading') }}</h3>

                {{-- Upload/replace galley PDF --}}
                @if(!$article->galley_pdf_path)
                    <p class="text-sm text-gray-500 mb-3">{{ __('dashboard.galley_upload_hint') }}</p>
                @else
                    <div class="flex items-center gap-4 mb-4">
                        <div class="text-sm text-gray-600">
                            <span class="text-gray-400">{{ __('dashboard.galley_upload_label') }}:</span>
                            <span class="text-blue-600 hover:text-blue-800">
                                <a href="{{ route('articles.galley-pdf', $article) }}" target="_blank">{{ basename($article->galley_pdf_path) }}</a>
                            </span>
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('editorial.upload-galley-pdf', $article) }}" enctype="multipart/form-data" class="inline">
                        @csrf
                        <div class="flex items-center gap-2">
                            <input type="file" name="galley_pdf" accept="application/pdf" required
                                class="text-sm border border-gray-300 rounded-md file:mr-3 file:py-1.5 file:px-3 file:text-sm file:font-medium file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                            <x-primary-button class="text-xs px-3 py-1.5">{{ $article->galley_pdf_path ? __('dashboard.galley_replace_button') : __('dashboard.galley_upload_button') }}</x-primary-button>
                        </div>
                        <x-input-error :messages="$errors->get('galley_pdf')" class="mt-1" />
                    </form>

                    @if($article->galley_pdf_path)
                        <form method="POST" action="{{ route('editorial.send-galley', $article) }}" class="inline">
                            @csrf
                            <x-primary-button class="bg-green-600 hover:bg-green-700 text-xs px-4 py-1.5">{{ __('dashboard.galley_send_button') }}</x-primary-button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        {{-- Galley Awaiting Approval --}}
        @if($article->isAwaitingApproval())
            <div class="bg-white rounded-lg border border-blue-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.galley_heading') }}</h3>
                <div class="flex items-center gap-4 mb-3">
                    <span class="text-sm px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">{{ __('dashboard.galley_awaiting_approval') }}</span>
                    @if($article->galley_pdf_path)
                        <a href="{{ route('articles.galley-pdf', $article) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800">
                            {{ basename($article->galley_pdf_path) }}
                        </a>
                    @endif
                </div>
                <p class="text-sm text-gray-500">
                    {{ __('dashboard.galley_sent_info') }}
                    {{ $article->galleySentBy?->full_name }},
                    {{ $article->galley_sent_at?->format('d.m.Y H:i') }}
                </p>
            </div>
        @endif

        {{-- Galley Approved --}}
        @if($article->isApproved() && !$article->isPublished())
            <div class="bg-white rounded-lg border border-green-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.galley_heading') }}</h3>
                <div class="flex items-center gap-4 mb-3">
                    <span class="text-sm px-2 py-0.5 rounded-full bg-green-50 text-green-700">{{ __('dashboard.galley_approved_info') }}</span>
                    @if($article->galley_pdf_path)
                        <a href="{{ route('articles.galley-pdf', $article) }}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800">
                            {{ basename($article->galley_pdf_path) }}
                        </a>
                    @endif
                </div>
                <p class="text-sm text-gray-500">
                    {{ __('article.galley_approved_by') }}
                    {{ $article->galleyApprovedBy?->full_name }},
                    {{ $article->galley_approved_at?->format('d.m.Y H:i') }}
                </p>
            </div>
        @endif

        {{-- Galley Revision History --}}
        @if($article->galleyRevisions->isNotEmpty())
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('article.revision_history_heading') }}</h3>
                <div class="space-y-3">
                    @foreach($article->galleyRevisions as $revision)
                        <div class="border border-gray-100 rounded-lg p-3">
                            <div class="flex items-center gap-3 mb-1">
                                <span class="text-sm text-gray-700">{{ $revision->requestedBy?->full_name }}</span>
                                <span class="text-xs text-gray-400">{{ $revision->created_at->format('d.m.Y H:i') }}</span>
                            </div>
                            @if($revision->comment)
                                <p class="text-sm text-gray-600">{{ $revision->comment }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Publish --}}
        @if($showPublish ?? false)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.publication_heading') }}</h3>
                <p class="text-sm text-gray-500 mb-3">
                    {{ __('dashboard.production_label') }} {{ $article->productionBy?->full_name }}, {{ $article->production_at?->format('d.m.Y H:i') }}
                </p>
                <form method="POST" action="{{ route('editorial.publish', $article) }}" class="flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="issue_id" :value="__('dashboard.issue_label')" />
                        <select id="issue_id" name="issue_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>
                            <option value="">{{ __('dashboard.select_issue') }}</option>
                            @foreach($issues as $issue)
                                <option value="{{ $issue->id }}">Том {{ $issue->volume }}, № {{ $issue->number }} ({{ $issue->year }}) {{ $issue->title ? '— ' . $issue->title : '' }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('issue_id')" class="mt-1" />
                    </div>
                    <x-primary-button>{{ __('dashboard.publish_button') }}</x-primary-button>
                </form>
            </div>
        @endif

        {{-- Withdraw --}}
        @if($showWithdraw ?? false)
            <div class="bg-white rounded-lg border border-gray-200 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">Отзыв статьи</h3>
                <p class="text-sm text-gray-500 mb-3">Отозвать статью из редакционного процесса. Статья будет снята с рассмотрения и не будет опубликована.</p>
                <form method="POST" action="{{ route('editorial.withdraw', $article) }}" onsubmit="return confirm('Вы уверены, что хотите отозвать статью? Это действие нельзя отменить.')">
                    @csrf
                    <div class="space-y-3">
                        <div>
                            <textarea name="reason" rows="3" required maxlength="5000"
                                placeholder="Причина отзыва..."
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm"></textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                        </div>
                        <x-danger-button>Отозвать статью</x-danger-button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Retract --}}
        @if($showRetract ?? false)
            <div class="bg-white rounded-lg border border-red-200 p-6">
                <h3 class="font-semibold text-red-700 mb-3">Ретрекшн (отзыв опубликованной статьи)</h3>
                <p class="text-sm text-gray-500 mb-3">Ретрекшн означает отзыв уже опубликованной статьи. Статья останется доступной на сайте с пометкой "Ретрекшн".</p>
                <form method="POST" action="{{ route('editorial.retract', $article) }}" onsubmit="return confirm('Вы уверены, что хотите отозвать (ретрекшн) опубликованную статью?')">
                    @csrf
                    <div class="space-y-3">
                        <div>
                            <textarea name="reason" rows="3" required maxlength="5000"
                                placeholder="Причина ретрекшна..."
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm"></textarea>
                            <x-input-error :messages="$errors->get('reason')" class="mt-1" />
                        </div>
                        <x-danger-button>Отозвать (ретрекшн)</x-danger-button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Corrections --}}
        @if($showCorrections ?? false)
            <div class="bg-white rounded-lg border border-gray-200 p-6" x-data="{ showForm: false }">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-900">Исправления (corrigendum / erratum)</h3>
                    <button @click="showForm = !showForm" class="text-sm text-primary hover:underline">
                        <span x-show="!showForm">Добавить исправление</span>
                        <span x-show="showForm" x-cloak>Отмена</span>
                    </button>
                </div>

                <div x-show="showForm" x-cloak class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <form method="POST" action="{{ route('editorial.corrections.store', $article) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Тип исправления</label>
                            <select name="type" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                                @foreach(\App\Enums\CorrectionType::cases() as $type)
                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Заголовок</label>
                            <input type="text" name="title" required maxlength="500"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
                            <textarea name="description" rows="3" required maxlength="10000"
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm"
                                placeholder="Что именно было исправлено..."></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Дата публикации исправления</label>
                                <input type="date" name="published_at" required value="{{ now()->format('Y-m-d') }}"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">PDF уведомления (необязательно)</label>
                                <input type="file" name="file" accept=".pdf"
                                    class="block w-full text-sm text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-sm file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                            </div>
                        </div>
                        <x-primary-button>Добавить исправление</x-primary-button>
                    </form>
                </div>

                @if($article->corrections->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($article->corrections as $correction)
                            <div class="border border-gray-100 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs px-1.5 py-0.5 rounded-full {{ $correction->type->badgeClass() }}">
                                            {{ $correction->type->label() }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">{{ $correction->title }}</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs text-gray-400">{{ $correction->published_at->format('d.m.Y') }}</span>
                                        <form method="POST" action="{{ route('editorial.corrections.destroy', [$article, $correction]) }}" onsubmit="return confirm('Удалить исправление?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-500 hover:underline">Удалить</button>
                                        </form>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-700">{{ $correction->description }}</p>
                                @if($correction->file_path)
                                    <div class="mt-2">
                                        <a href="{{ Storage::disk('local')->url($correction->file_path) }}" target="_blank"
                                            class="text-xs text-primary hover:underline">Скачать PDF уведомления</a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400 text-center py-4">Нет исправлений</p>
                @endif
            </div>
        @endif

        {{-- Discussions --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6" x-data="discussions({{ $article->id }})" id="discussions">
            <h3 class="font-semibold text-gray-900 mb-4">Обсуждения</h3>

            {{-- Filter tabs --}}
            <div class="flex items-center gap-1 mb-4 border-b border-gray-200 pb-2">
                @php $activeTab = request('tab', 'all'); @endphp
                <a href="{{ url()->current() }}#discussions" class="text-xs px-3 py-1.5 rounded-t-md {{ $activeTab === 'all' ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700' }}">Все</a>
                <a href="{{ url()->current() }}?tab=article#discussions" class="text-xs px-3 py-1.5 rounded-t-md {{ $activeTab === 'article' ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700' }}">Общие</a>
                <a href="{{ url()->current() }}?tab=editorial#discussions" class="text-xs px-3 py-1.5 rounded-t-md {{ $activeTab === 'editorial' ? 'bg-gray-100 text-gray-900 font-medium' : 'text-gray-500 hover:text-gray-700' }}">Редакционные</a>
            </div>

            {{-- New thread form --}}
            <form method="POST" action="{{ route('editorial.discussions.store', $article) }}" class="mb-6 p-4 bg-gray-50 rounded-lg">
                @csrf
                <div>
                    <textarea name="message" rows="2" required maxlength="5000"
                        placeholder="Новое сообщение..."
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm"></textarea>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <select name="scope" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary">
                        <option value="article">Общее</option>
                        <option value="editorial">Редакционное</option>
                    </select>
                    <x-primary-button class="text-xs px-3 py-1">Отправить</x-primary-button>
                </div>
            </form>

            {{-- Threads list --}}
            @php
                $visibleDiscussions = $article->discussions
                    ->where('parent_id', null)
                    ->filter(fn($d) => $d->isVisibleTo(auth()->user(), $article))
                    ->when($activeTab !== 'all', fn($c) => $c->filter(fn($d) => $d->scope->value === $activeTab))
                    ->sortByDesc('created_at');
            @endphp

            @if($visibleDiscussions->isEmpty())
                <p class="text-sm text-gray-400 text-center py-4">Нет обсуждений</p>
            @else
                <div class="space-y-4">
                    @foreach($visibleDiscussions as $thread)
                        <div class="border border-gray-100 rounded-lg p-4 {{ $thread->is_resolved ? 'bg-gray-50 opacity-60' : '' }}">
                            <div class="flex items-start justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    @if($thread->wasUnread)
                                        <span class="inline-block w-2 h-2 rounded-full bg-blue-500 shrink-0 mt-1.5" title="Новое сообщение"></span>
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $thread->user->full_name }}</span>
                                    <span class="text-xs px-1.5 py-0.5 rounded-full {{ $thread->scope === App\Enums\DiscussionScope::Editorial ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ $thread->scope->label() }}
                                    </span>
                                    @if($thread->review_id)
                                        <span class="text-xs text-gray-400">Рецензия #{{ $thread->review_id }}</span>
                                    @endif
                                    <span class="text-xs text-gray-400">{{ $thread->created_at->format('d.m.Y H:i') }}</span>
                                </div>
                                @if(!$thread->is_resolved)
                                    <form method="POST" action="{{ route('discussions.resolve', $thread) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-gray-400 hover:text-green-600">Решено</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('discussions.reopen', $thread) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-gray-400 hover:text-blue-600">Переоткрыть</button>
                                    </form>
                                @endif
                            </div>
                            <p class="text-sm text-gray-700 leading-relaxed">{{ $thread->message }}</p>

                            {{-- Replies --}}
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

                            {{-- Reply form --}}
                            @if(!$thread->is_resolved)
                                <form method="POST" action="{{ route('editorial.discussions.store', $article) }}" class="mt-3 flex items-start gap-2">
                                    @csrf
                                    <input type="hidden" name="parent_id" value="{{ $thread->id }}">
                                    <input type="hidden" name="scope" value="{{ $thread->scope->value }}">
                                    @if($thread->review_id)
                                        <input type="hidden" name="review_id" value="{{ $thread->review_id }}">
                                    @endif
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

        <div class="text-center">
            <a href="{{ route('editorial.index') }}" class="text-sm text-gray-400 hover:text-gray-600">&larr; {{ __('common.back_to_list') }}</a>
        </div>
    </div>
</x-app-layout>
