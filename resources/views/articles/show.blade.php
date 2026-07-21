@extends('layouts.public')

@section('title', $article->title . ' — Global Campus RU')
@section('description', Str::limit(strip_tags($article->abstract_ru ?? ''), 160))
@section('og_title', $article->title)
@section('og_description', Str::limit(strip_tags($article->abstract_ru ?? ''), 200))
@section('og_type', 'article')

@push('meta')
@php
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'ScholarlyArticle',
    'headline' => $article->title,
    'author' => $article->authors->map(fn ($a) => array_filter([
        '@type' => 'Person',
        'name' => $a->full_name,
        'identifier' => $a->orcid ? "https://orcid.org/{$a->orcid}" : null,
    ]))->values()->toArray(),
    'publisher' => ['@type' => 'Organization', 'name' => config('app.name')],
];
if ($article->abstract_ru) $jsonLd['description'] = Str::limit($article->abstract_ru, 300);
if ($article->doi) $jsonLd['identifier'] = ['@type' => 'PropertyValue', 'propertyID' => 'DOI', 'value' => $article->doi];
if ($article->published_at) $jsonLd['datePublished'] = $article->published_at->toIso8601String();
if ($article->keywords) $jsonLd['keywords'] = implode(', ', $article->keywords);
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}</script>

<meta name="citation_title" content="{{ $article->title }}">
@foreach($article->authors as $author)
<meta name="citation_author" content="{{ $author->full_name }}">
@endforeach
@if($article->published_at)
<meta name="citation_publication_date" content="{{ $article->published_at->format('Y/m/d') }}">
@endif
<meta name="citation_journal_title" content="{{ config('app.name') }}">
@if($article->issue)
<meta name="citation_volume" content="{{ $article->issue->volume }}">
<meta name="citation_issue" content="{{ $article->issue->number }}">
@endif
@if($article->first_page)
<meta name="citation_firstpage" content="{{ $article->first_page }}">
@endif
@if($article->last_page)
<meta name="citation_lastpage" content="{{ $article->last_page }}">
@endif
@if($article->doi)
<meta name="citation_doi" content="{{ $article->doi }}">
@endif
@if($article->pdf_path)
<meta name="citation_pdf_url" content="{{ route('articles.pdf', $article) }}">
@endif
<meta name="citation_abstract_html_url" content="{{ route('articles.show', $article) }}">
<meta name="citation_language" content="ru">
@if($article->keywords)
<meta name="citation_keywords" content="{{ implode(', ', $article->keywords) }}">
@endif
@if($electronicIssn)
<meta name="citation_issn" content="{{ $electronicIssn }}">
@elseif($printIssn)
<meta name="citation_issn" content="{{ $printIssn }}">
@endif
@endpush

@section('content')
    <section class="py-12 lg:py-16">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('articles.index') }}" class="text-sm text-primary hover:underline">&larr; {{ __('pages.articles_back') }}</a>
            </div>

            {{-- Retraction banner --}}
            @if($article->isRetracted())
                <div class="mb-6 p-5 bg-red-50 border-2 border-red-300 rounded-lg">
                    <h2 class="text-lg font-bold text-red-700 mb-2">Статья отозвана (ретрекшн)</h2>
                    @if($article->retraction_reason)
                        <p class="text-sm text-red-600">{{ $article->retraction_reason }}</p>
                    @endif
                    @if($article->retracted_at)
                        <p class="text-xs text-red-400 mt-2">Дата ретрекшна: {{ $article->retracted_at->format('d.m.Y') }}</p>
                    @endif
                </div>
            @endif

            <div class="mb-6">
                <div class="flex flex-wrap items-center gap-3 mb-3">
                    @if($article->category)
                        <a href="{{ route('articles.index', ['category' => $article->category->id]) }}" class="text-xs bg-primary/10 text-primary px-2 py-1 rounded font-semibold hover:bg-primary/20 transition">{{ $article->category->name }}</a>
                    @endif
                    @if($article->issue)
                        <a href="{{ route('issues.show', $article->issue) }}" class="text-xs text-gray-500 hover:text-primary">
                            Том {{ $article->issue->volume }}, №{{ $article->issue->number }} ({{ $article->issue->year }})
                        </a>
                    @endif
                </div>
                <h1 class="text-3xl font-bold font-serif text-primary">{{ $article->title }}</h1>
                <div class="flex items-center gap-3 mt-2 text-xs text-gray-400">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        {{ $article->views_count }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        {{ $article->downloads_count }}
                    </span>
                </div>
            </div>

            {{-- Authors --}}
            @if($article->authors->isNotEmpty())
                <div class="mb-6">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('common.authors') }}</h2>
                    <div class="space-y-2">
                        @foreach($article->authors as $author)
                        <div>
                            <a href="{{ route('authors.show', $author) }}" class="font-semibold text-primary hover:underline">{{ $author->full_name }}</a>
                            @if($author->degree || $author->position)
                                <span class="text-sm text-gray-500">
                                    — {{ collect([$author->degree, $author->position])->filter()->join(', ') }}
                                </span>
                            @endif
                            @if($author->organization)
                                <div class="text-sm text-gray-500">{{ $author->organization }}</div>
                            @endif
                            @if($author->orcid)
                                <div class="text-xs text-gray-400">
                                    <a href="https://orcid.org/{{ $author->orcid }}" target="_blank" rel="noopener" class="hover:text-primary">ORCID: {{ $author->orcid }}</a>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            @endif


            {{-- Abstract RU --}}
            @if($article->abstract_ru)
                <div class="mb-6 p-5 bg-gray-50 rounded-lg border border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('common.abstract') }}</h2>
                    <p class="text-gray-700 leading-relaxed">{{ $article->abstract_ru }}</p>
                </div>
            @endif

            {{-- Abstract EN --}}
            @if($article->abstract_en)
                <div class="mb-6 p-5 bg-gray-50 rounded-lg border border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ __('common.abstract_en') }}</h2>
                    <p class="text-gray-700 leading-relaxed italic">{{ $article->abstract_en }}</p>
                </div>
            @endif

            {{-- DOI --}}
            @if($article->doi)
                <div class="mb-6">
                    <span class="text-sm font-semibold text-gray-500">DOI:</span>
                    <a href="https://doi.org/{{ $article->doi }}" target="_blank" rel="noopener" class="text-sm text-primary hover:underline">{{ $article->doi }}</a>
                </div>
            @endif

            {{-- License --}}
            @if($publicationLicense)
                <div class="mb-6">
                    <span class="text-sm font-semibold text-gray-500">{{ __('article.license') }}:</span>
                    @if($publicationLicense->url())
                        <a href="{{ $publicationLicense->url() }}" target="_blank" rel="noopener" class="text-sm text-primary hover:underline">{{ $publicationLicense->label() }}</a>
                    @else
                        <span class="text-sm text-gray-700">{{ $publicationLicense->label() }}</span>
                    @endif
                </div>
            @endif

            {{-- Keywords --}}
            @if($article->keywords)
                <div class="mb-6">
                    <span class="text-sm font-semibold text-gray-500">Ключевые слова:</span>
                    <div class="flex flex-wrap gap-1.5 mt-1">
                        @foreach($article->keywords as $kw)
                            <a href="{{ route('articles.index', ['keyword' => $kw]) }}" class="text-xs bg-primary/10 text-primary px-2 py-1 rounded hover:bg-primary/20 transition">{{ $kw }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- How to cite --}}
            @if($article->doi || $article->authors->isNotEmpty())
                <div class="mb-6 p-5 bg-gray-50 rounded-lg border border-gray-200" x-data="{ format: 'gost', copied: false }">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ __('common.how_to_cite') }}</h2>

                    <div class="flex gap-2 mb-3">
                        <button @click="format = 'gost'" :class="format === 'gost' ? 'bg-primary text-white' : 'bg-white text-gray-700 border border-gray-300'" class="text-xs px-3 py-1.5 rounded transition">{{ __('common.gost') }}</button>
                        <button @click="format = 'apa'" :class="format === 'apa' ? 'bg-primary text-white' : 'bg-white text-gray-700 border border-gray-300'" class="text-xs px-3 py-1.5 rounded transition">{{ __('common.apa') }}</button>
                    </div>

                    {{-- GOST format --}}
                    <div x-show="format === 'gost'" x-ref="gost" class="text-sm text-gray-700 leading-relaxed">
                        {{ $article->authors->map(fn ($a) => $a->gost_name)->join(', ') }}
                        {{ $article->title }} // {{ config('app.name') }}.
                        @if($article->issue)
                            — {{ $article->issue->year }}.
                            — Т. {{ $article->issue->volume }}, № {{ $article->issue->number }}.
                        @endif
                        @if($article->doi)
                            — DOI: {{ $article->doi }}
                        @endif
                    </div>

                    {{-- APA format --}}
                    <div x-show="format === 'apa'" x-cloak x-ref="apa" class="text-sm text-gray-700 leading-relaxed italic">
                        {{ $article->authors->map(fn ($a) => $a->gost_name)->join(', ') }}
                        @if($article->issue)
                            ({{ $article->issue->year }}).
                        @endif
                        {{ $article->title }}.
                        <span class="not-italic">{{ config('app.name') }}</span>@if($article->issue), <span class="not-italic">{{ $article->issue->volume }}</span>({{ $article->issue->number }})@endif.
                        @if($article->doi)
                            https://doi.org/{{ $article->doi }}
                        @endif
                    </div>

                    <div class="mt-3 flex items-center gap-4">
                        <button
                            @click="
                                const text = format === 'gost' ? $refs.gost.innerText : $refs.apa.innerText;
                                navigator.clipboard.writeText(text.replace(/\s+/g, ' ').trim());
                                copied = true;
                                setTimeout(() => copied = false, 2000);
                            "
                            class="text-xs text-primary hover:underline flex items-center gap-1"
                        >
                            <span x-show="!copied">{{ __('common.copy') }}</span>
                            <span x-show="copied" x-cloak>{{ __('common.copied') }}</span>
                        </button>
                        <a href="{{ route('articles.export.bibtex', $article) }}" class="text-xs text-primary hover:underline flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            BibTeX
                        </a>
                        <a href="{{ route('articles.export.ris', $article) }}" class="text-xs text-primary hover:underline flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            RIS
                        </a>
                        <a href="{{ route('articles.export.jats', $article) }}" class="text-xs text-primary hover:underline flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            JATS XML
                        </a>
                    </div>
                </div>
            @endif

            {{-- Body --}}
            @if($article->body)
                <div class="prose prose-lg max-w-none text-gray-700 leading-relaxed">
                    @purify($article->body)
                </div>
            @endif

            {{-- Supplementary Files --}}
            @php
                $publicFiles = $article->files->where('visibility', \App\Enums\ArticleFileVisibility::Public);
            @endphp
            @if($publicFiles->isNotEmpty())
                <div class="mb-8 p-5 bg-gray-50 rounded-lg border border-gray-200">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">{{ __('common.supplementary_materials') }}</h2>
                    <div class="space-y-4">
                        @foreach($publicFiles as $file)
                            <div class="flex items-start gap-4">
                                {{-- Preview/Icon --}}
                                <div class="flex-shrink-0">
                                    @if($file->isImage() && $file->thumbnail_url)
                                        <a href="{{ route('article-files.download', $file) }}" target="_blank" class="block">
                                            <img src="{{ $file->thumbnail_url }}" alt="{{ $file->original_name }}" class="w-20 h-20 object-cover rounded-lg hover:opacity-90 transition">
                                        </a>
                                    @else
                                        <div class="w-14 h-14 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <h3 class="text-sm font-medium text-gray-900">{{ $file->original_name }}</h3>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $file->file_type->label() }} • {{ $file->formatted_size }}
                                        @if($file->language)
                                            • {{ strtoupper($file->language) }}
                                        @endif
                                    </p>
                                    @if($file->license)
                                        <p class="text-xs text-gray-400 mt-1">
                                            @if($file->license->url())
                                                <a href="{{ $file->license->url() }}" target="_blank" rel="noopener" class="hover:text-primary">{{ $file->license->label() }}</a>
                                            @else
                                                {{ $file->license->label() }}
                                            @endif
                                        </p>
                                    @endif
                                </div>

                                {{-- Download Button --}}
                                <div class="flex-shrink-0">
                                    <a href="{{ route('article-files.download', $file) }}"
                                       class="inline-flex items-center gap-1.5 bg-primary hover:bg-primary-light text-white px-4 py-2 rounded text-sm font-medium transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        {{ __('common.download') }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Corrections --}}
            @php
                $articleCorrections = $article->corrections ?? collect();
            @endphp
            @if($articleCorrections->isNotEmpty())
                <div class="mb-6 p-5 bg-yellow-50 rounded-lg border border-yellow-200">
                    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Исправления</h2>
                    <div class="space-y-3">
                        @foreach($articleCorrections as $correction)
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs px-1.5 py-0.5 rounded-full {{ $correction->type->badgeClass() }}">
                                        {{ $correction->type->label() }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">{{ $correction->title }}</span>
                                    <span class="text-xs text-gray-400">{{ $correction->published_at->format('d.m.Y') }}</span>
                                </div>
                                <p class="text-sm text-gray-700">{{ $correction->description }}</p>
                                @if($correction->file_path)
                                    <a href="{{ Storage::disk('local')->url($correction->file_path) }}" target="_blank"
                                        class="text-xs text-primary hover:underline">Скачать PDF уведомления</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Crossmark button --}}
            @if($article->doi)
                @php $crossmarkConfig = config('services.crossref.crossmark'); @endphp
                @if(!empty($crossmarkConfig['policy_url']))
                    <div class="mb-6 flex items-center gap-2">
                        <a href="https://crossmark.crossref.org/dialog/?doi={{ urlencode($article->doi) }}&amp;domain={{ urlencode($crossmarkConfig['domains'][0] ?? parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost') }}&amp;date={{ $article->published_at?->format('Y-m-d') ?? now()->format('Y-m-d') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-700 transition">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                            Crossmark
                        </a>
                    </div>
                @endif
            @endif

            {{-- PDF viewer & download --}}
            @if($article->pdf_path)
                <div class="mt-8" x-data="{ showViewer: false }">
                    <div class="flex flex-wrap gap-3 mb-4">
                        <button @click="showViewer = !showViewer"
                                class="inline-block bg-primary hover:bg-primary-light text-white px-6 py-3 rounded font-semibold transition"
                                x-text="showViewer ? '{{ __('common.hide_pdf') }}' : '{{ __('common.view_pdf') }}'">
                        </button>
                        <a href="{{ route('articles.pdf', ['article' => $article, 'download' => 1]) }}"
                           class="inline-block bg-accent hover:bg-accent-light text-white px-6 py-3 rounded font-semibold transition"
                           target="_blank" download>
                            {{ __('common.download_pdf') }}
                        </a>
                    </div>
                    <div x-show="showViewer" x-cloak x-transition class="mt-4">
                        <iframe src="{{ route('articles.pdf', $article) }}"
                                class="w-full rounded-lg border border-gray-200"
                                style="height: 80vh;"
                                title="PDF: {{ $article->title }}">
                            <p>{{ __('common.pdf_not_supported') }}
                               <a href="{{ route('articles.pdf', $article) }}">{{ __('common.download_file') }}</a>.
                            </p>
                        </iframe>
                    </div>
                </div>
            @endif

            {{-- Other publications by authors --}}
            @foreach($article->authors as $author)
                @if($authorArticles[$author->id]->isNotEmpty())
                    <div class="mb-6 p-5 bg-gray-50 rounded-lg border border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">
                            {{ __('common.other_publications') }} {{ $author->full_name }}
                        </h3>
                        <ul class="space-y-2">
                            @foreach($authorArticles[$author->id] as $otherArticle)
                                <li>
                                    <a href="{{ route('articles.show', $otherArticle) }}" class="text-primary hover:underline">{{ $otherArticle->title }}</a>
                                    @if($otherArticle->issue)
                                        <span class="text-xs text-gray-500">
                                            — Том {{ $otherArticle->issue->volume }}, №{{ $otherArticle->issue->number }} ({{ $otherArticle->issue->year }})
                                        </span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        @if($authorIssues[$author->id]->isNotEmpty())
                            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-gray-200">
                                <span class="text-xs text-gray-500 self-center">{{ __('common.issues_label') }}</span>
                                @foreach($authorIssues[$author->id] as $issue)
                                    <a href="{{ route('issues.show', $issue) }}" class="text-xs bg-primary/10 text-primary px-2 py-1 rounded hover:bg-primary/20 transition">
                                        Том {{ $issue->volume }}, №{{ $issue->number }} ({{ $issue->year }})
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        <a href="{{ route('authors.show', $author) }}" class="text-sm text-primary hover:underline mt-3 inline-block">
                            Все публикации автора &rarr;
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endsection
