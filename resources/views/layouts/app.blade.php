<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }} — {{ __('nav.personal_account') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            <main class="flex-1 py-6">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    @isset($header)
                        <div class="mb-6">{{ $header }}</div>
                    @endisset
                    {{ $slot }}
                </div>
            </main>

            <footer class="bg-primary-dark text-gray-400 text-xs text-center py-4 mt-auto">
                &copy; {{ date('Y') }} {{ config('app.name') }}
            </footer>
        </div>
    </body>
</html>
