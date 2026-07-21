<nav x-data="{ open: false }" class="bg-primary">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <div class="flex items-center gap-6">
                <a href="{{ route('home') }}" class="text-white font-bold text-lg">{{ config('app.name') }}</a>
                <div class="hidden sm:flex items-center gap-1 text-sm">
                    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded text-sm {{ request()->routeIs('dashboard') ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition">{{ __('nav.dashboard') }}</a>
                    <a href="{{ route('submissions.create') }}" class="px-3 py-1.5 rounded text-sm {{ request()->routeIs('submissions.create') ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition">{{ __('nav.submit_article') }}</a>
                    @if(auth()->user()->hasRole('reviewer'))
                    <a href="{{ route('reviews.index') }}" class="px-3 py-1.5 rounded text-sm {{ request()->routeIs('reviews.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition">{{ __('nav.reviews') }}</a>
                    @endif
                    @if(auth()->user()->can('manage-submissions'))
                    <a href="{{ route('editorial.index') }}" class="px-3 py-1.5 rounded text-sm {{ request()->routeIs('editorial.*') ? 'bg-white/20 text-white' : 'text-white/70 hover:text-white hover:bg-white/10' }} transition">{{ __('nav.editorial') }}</a>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-3">
                <a href="{{ route('home') }}" class="text-white/50 hover:text-white text-xs transition">{{ __('nav.site') }}</a>

                {{-- Notification bell --}}
                <a href="{{ route('notifications.index') }}" class="relative text-white/70 hover:text-white transition" title="Уведомления">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @php $unread = auth()->user()->unreadNotifications->count(); @endphp
                    @if($unread > 0)
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">{{ $unread > 9 ? '9+' : $unread }}</span>
                    @endif
                </a>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-1 text-sm text-white/80 hover:text-white transition">
                            {{ Auth::user()->full_name }}
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('nav.profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">{{ __('nav.logout') }}</x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <button @click="open = !open" class="sm:hidden text-white/80 hover:text-white">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    <path x-show="open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak class="sm:hidden bg-primary-dark border-t border-white/10">
        <div class="px-3 py-2 space-y-1">
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded text-sm {{ request()->routeIs('dashboard') ? 'bg-white/15 text-white' : 'text-white/70 hover:text-white' }}">{{ __('nav.dashboard') }}</a>
            <a href="{{ route('submissions.create') }}" class="block px-3 py-2 rounded text-sm {{ request()->routeIs('submissions.create') ? 'bg-white/15 text-white' : 'text-white/70 hover:text-white' }}">{{ __('nav.submit_article') }}</a>
            @if(auth()->user()->hasRole('reviewer'))
            <a href="{{ route('reviews.index') }}" class="block px-3 py-2 rounded text-sm {{ request()->routeIs('reviews.*') ? 'bg-white/15 text-white' : 'text-white/70 hover:text-white' }}">{{ __('nav.reviews') }}</a>
            @endif
            @if(auth()->user()->can('manage-submissions'))
            <a href="{{ route('editorial.index') }}" class="block px-3 py-2 rounded text-sm {{ request()->routeIs('editorial.*') ? 'bg-white/15 text-white' : 'text-white/70 hover:text-white' }}">{{ __('nav.editorial') }}</a>
            @endif
        </div>
        <div class="border-t border-white/10 px-4 py-3">
            <div class="text-sm text-white">{{ Auth::user()->full_name }}</div>
            <div class="text-xs text-white/50">{{ Auth::user()->email }}</div>
            <div class="mt-2 flex gap-4 text-xs">
                <a href="{{ route('profile.edit') }}" class="text-white/60 hover:text-white">{{ __('nav.profile') }}</a>
                <a href="{{ route('home') }}" class="text-white/60 hover:text-white">{{ __('nav.site') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-white/60 hover:text-white">{{ __('nav.logout') }}</button>
                </form>
            </div>
        </div>
    </div>
</nav>
