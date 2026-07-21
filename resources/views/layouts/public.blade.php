<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', config('journal.subtitle_full'))">

    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', config('journal.subtitle_full'))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
    @endif
    <meta name="twitter:card" content="summary">
    <link rel="canonical" href="{{ url()->current() }}">
    @stack('meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-white text-gray-900">

    {{-- Header --}}
    <header class="bg-primary text-white" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{-- Top bar --}}
            <div class="flex items-center justify-between py-3 border-b border-primary-light text-sm">
                <div class="hidden md:flex items-center gap-4 text-gray-300">
                    <a href="mailto:{{ $siteSettings['email'] }}" class="hover:text-white">{{ $siteSettings['email'] }}</a>
                    <span>|</span>
                    <a href="tel:{{ $siteSettings['phone_raw'] }}" class="hover:text-white">{{ $siteSettings['phone'] }}</a>
                </div>
                <div class="flex items-center gap-3">
                    @if($siteSettings['vk'])
                    <a href="{{ $siteSettings['vk'] }}" target="_blank" rel="noopener" class="hover:text-gray-300" title="VK">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1-1.49-1.135-1.744-1.135-.356 0-.458.102-.458.593v1.575c0 .424-.135.678-1.253.678-1.846 0-3.896-1.118-5.335-3.202C4.624 10.857 4.03 8.57 4.03 8.096c0-.254.102-.491.593-.491h1.744c.44 0 .61.203.78.678.847 2.458 2.28 4.612 2.87 4.612.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.204.17-.407.44-.407h2.744c.373 0 .508.203.508.643v3.473c0 .372.17.508.271.508.22 0 .407-.136.813-.542 1.253-1.406 2.148-3.574 2.148-3.574.119-.254.322-.491.763-.491h1.744c.525 0 .644.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.78 1.202 1.253.746.847 1.32 1.558 1.473 2.05.17.49-.085.744-.576.744z"/></svg>
                    </a>
                    @endif
                    @if($siteSettings['telegram'])
                    <a href="{{ $siteSettings['telegram'] }}" target="_blank" rel="noopener" class="hover:text-gray-300" title="Telegram">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                    </a>
                    @endif
                    @if($siteSettings['whatsapp'])
                    <a href="{{ $siteSettings['whatsapp'] }}" target="_blank" rel="noopener" class="hover:text-gray-300" title="WhatsApp">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                    </a>
                    @endif
                    @if($siteSettings['rutube'])
                    <a href="{{ $siteSettings['rutube'] }}" target="_blank" rel="noopener" class="hover:text-gray-300" title="RuTube">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 12.533l-7.332 4.5a.638.638 0 0 1-.974-.533v-9a.637.637 0 0 1 .974-.533l7.332 4.5a.636.636 0 0 1 0 1.066z"/></svg>
                    </a>
                    @endif
                    <span class="text-gray-500 mx-1">|</span>
                    @auth
                        <a href="{{ route('dashboard') }}" class="hover:text-gray-300 text-sm">{{ __('nav.personal_account') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="hover:text-gray-300 text-sm">{{ __('nav.login') }}</a>
                        <a href="{{ route('register') }}" class="hover:text-gray-300 text-sm">{{ __('nav.register') }}</a>
                    @endauth
                </div>
            </div>

            {{-- Main nav --}}
            <div class="flex items-center justify-between py-4">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <div>
                        <div class="text-xl font-bold tracking-wide">{{ config('app.name') }}</div>
                        <div class="text-xs text-gray-300 tracking-wider uppercase">{{ config('journal.subtitle') }}</div>
                    </div>
                </a>

                {{-- Desktop nav --}}
                <nav class="hidden lg:flex items-center gap-6 text-sm">
                    <a href="{{ route('home') }}" class="hover:text-gray-300 {{ request()->routeIs('home') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.home') }}</a>
                    <a href="{{ route('about') }}" class="hover:text-gray-300 {{ request()->routeIs('about') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.about') }}</a>
                    <a href="{{ route('issues.index') }}" class="hover:text-gray-300 {{ request()->routeIs('issues.*') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.issues') }}</a>
                    <a href="{{ route('articles.index') }}" class="hover:text-gray-300 {{ request()->routeIs('articles.*') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.articles') }}</a>
                    <a href="{{ route('editorial-board') }}" class="hover:text-gray-300 {{ request()->routeIs('editorial-board') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.editorial_board') }}</a>
                    <a href="{{ route('for-authors') }}" class="hover:text-gray-300 {{ request()->routeIs('for-authors') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.for_authors') }}</a>
                    <a href="{{ route('news.index') }}" class="hover:text-gray-300 {{ request()->routeIs('news.*') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.news') }}</a>
                    <a href="{{ route('conferences.index') }}" class="hover:text-gray-300 {{ request()->routeIs('conferences.*') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.conferences') }}</a>
                    <a href="{{ route('events.index') }}" class="hover:text-gray-300 {{ request()->routeIs('events.*') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.events') }}</a>
                    <a href="{{ route('contacts') }}" class="hover:text-gray-300 {{ request()->routeIs('contacts') ? 'text-white font-semibold border-b-2 border-white pb-1' : '' }}">{{ __('nav.contacts') }}</a>
                    <div class="relative" x-data="{ searchOpen: false }">
                        <button @click="searchOpen = !searchOpen; $nextTick(() => searchOpen && $refs.searchInput.focus())" class="hover:text-gray-300" title="{{ __('common.search_tooltip') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                        <form x-show="searchOpen" x-cloak x-transition @click.outside="searchOpen = false" action="{{ route('search') }}" method="GET" class="absolute right-0 top-full mt-2 z-50">
                            <input x-ref="searchInput" type="text" name="q" placeholder="{{ __('nav.search') }}..." class="w-64 text-sm text-gray-900 border-0 rounded-lg shadow-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                        </form>
                    </div>
                </nav>

                {{-- Mobile burger --}}
                <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        <path x-show="mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Mobile nav --}}
            <div x-show="mobileOpen" x-cloak class="lg:hidden pb-4 space-y-2 text-sm">
                <form action="{{ route('search') }}" method="GET" class="pb-2">
                    <input type="text" name="q" placeholder="{{ __('pages.search_placeholder') }}" class="w-full text-sm text-gray-900 border-0 rounded-lg px-4 py-2 focus:ring-2 focus:ring-primary">
                </form>
                <a href="{{ route('home') }}" class="block py-2 hover:text-gray-300">{{ __('nav.home') }}</a>
                <a href="{{ route('about') }}" class="block py-2 hover:text-gray-300">{{ __('nav.about') }}</a>
                <a href="{{ route('issues.index') }}" class="block py-2 hover:text-gray-300">{{ __('nav.issues') }}</a>
                <a href="{{ route('articles.index') }}" class="block py-2 hover:text-gray-300">{{ __('nav.articles') }}</a>
                <a href="{{ route('editorial-board') }}" class="block py-2 hover:text-gray-300">{{ __('nav.editorial_board') }}</a>
                <a href="{{ route('for-authors') }}" class="block py-2 hover:text-gray-300">{{ __('nav.for_authors') }}</a>
                <a href="{{ route('news.index') }}" class="block py-2 hover:text-gray-300">{{ __('nav.news') }}</a>
                <a href="{{ route('conferences.index') }}" class="block py-2 hover:text-gray-300">{{ __('nav.conferences') }}</a>
                <a href="{{ route('events.index') }}" class="block py-2 hover:text-gray-300">{{ __('nav.events') }}</a>
                <a href="{{ route('contacts') }}" class="block py-2 hover:text-gray-300">{{ __('nav.contacts') }}</a>
            </div>
        </div>
    </header>

    {{-- Content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-primary-dark text-gray-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- About --}}
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">{{ config('app.name') }}</h3>
                    <p class="text-sm leading-relaxed">{{ config('journal.description') }}</p>
                </div>

                {{-- Contacts --}}
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">{{ __('nav.contacts') }}</h3>
                    <div class="text-sm space-y-2">
                        <p>{{ config('journal.address') }}</p>
                        <p>E-mail: <a href="mailto:{{ $siteSettings['email'] }}" class="text-gray-100 hover:text-white">{{ $siteSettings['email'] }}</a></p>
                        <p>Тел: <a href="tel:{{ $siteSettings['phone_raw'] }}" class="text-gray-100 hover:text-white">{{ $siteSettings['phone'] }}</a></p>
                    </div>
                </div>

                {{-- Links --}}
                <div>
                    <h3 class="text-white font-bold text-lg mb-4">{{ __('nav.sections') }}</h3>
                    <ul class="text-sm space-y-2">
                        <li><a href="{{ route('about') }}" class="hover:text-white">{{ __('nav.about') }}</a></li>
                        <li><a href="{{ route('issues.index') }}" class="hover:text-white">{{ __('nav.issues') }}</a></li>
                        <li><a href="{{ route('articles.index') }}" class="hover:text-white">{{ __('nav.articles') }}</a></li>
                        <li><a href="{{ route('editorial-board') }}" class="hover:text-white">{{ __('nav.editorial_board') }}</a></li>
                        <li><a href="{{ route('for-authors') }}" class="hover:text-white">{{ __('nav.for_authors') }}</a></li>
                        <li><a href="{{ route('news.index') }}" class="hover:text-white">{{ __('nav.news') }}</a></li>
                        <li><a href="{{ route('conferences.index') }}" class="hover:text-white">{{ __('nav.conferences') }}</a></li>
                        <li><a href="{{ route('events.index') }}" class="hover:text-white">{{ __('nav.events') }}</a></li>
                        <li><a href="{{ route('contacts') }}" class="hover:text-white">{{ __('nav.contacts') }}</a></li>
                    </ul>
                    <div class="flex items-center gap-3 mt-4">
                        @if($siteSettings['vk'])
                        <a href="{{ $siteSettings['vk'] }}" target="_blank" rel="noopener" class="hover:text-white" title="VK">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.864-.525-2.05-1.727-1.033-1-1.49-1.135-1.744-1.135-.356 0-.458.102-.458.593v1.575c0 .424-.135.678-1.253.678-1.846 0-3.896-1.118-5.335-3.202C4.624 10.857 4.03 8.57 4.03 8.096c0-.254.102-.491.593-.491h1.744c.44 0 .61.203.78.678.847 2.458 2.28 4.612 2.87 4.612.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.204.17-.407.44-.407h2.744c.373 0 .508.203.508.643v3.473c0 .372.17.508.271.508.22 0 .407-.136.813-.542 1.253-1.406 2.148-3.574 2.148-3.574.119-.254.322-.491.763-.491h1.744c.525 0 .644.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.78 1.202 1.253.746.847 1.32 1.558 1.473 2.05.17.49-.085.744-.576.744z"/></svg>
                        </a>
                        @endif
                        @if($siteSettings['telegram'])
                        <a href="{{ $siteSettings['telegram'] }}" target="_blank" rel="noopener" class="hover:text-white" title="Telegram">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        </a>
                        @endif
                        @if($siteSettings['whatsapp'])
                        <a href="{{ $siteSettings['whatsapp'] }}" target="_blank" rel="noopener" class="hover:text-white" title="WhatsApp">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                        </a>
                        @endif
                        @if($siteSettings['rutube'])
                        <a href="{{ $siteSettings['rutube'] }}" target="_blank" rel="noopener" class="hover:text-white" title="RuTube">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 12.533l-7.332 4.5a.638.638 0 0 1-.974-.533v-9a.637.637 0 0 1 .974-.533l7.332 4.5a.636.636 0 0 1 0 1.066z"/></svg>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-600 mt-8 pt-6 text-sm text-center">
                <p>&copy; {{ date('Y') }} {{ config('journal.copyright') }}</p>
                @if($siteSettings['print_issn'] || $siteSettings['electronic_issn'])
                <p class="mt-2">
                    @if($siteSettings['print_issn'])ISSN {{ $siteSettings['print_issn'] }} (Print)@endif
                    @if($siteSettings['print_issn'] && $siteSettings['electronic_issn']), @endif
                    @if($siteSettings['electronic_issn'])ISSN {{ $siteSettings['electronic_issn'] }} (Online)@endif
                </p>
                @endif
            </div>
        </div>
    </footer>

</body>
</html>
