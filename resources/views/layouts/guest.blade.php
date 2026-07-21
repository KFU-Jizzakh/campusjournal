<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-8">
            <a href="{{ route('home') }}" class="mb-8 text-center">
                <div class="text-2xl font-bold text-primary">{{ config('app.name') }}</div>
                <div class="text-xs text-gray-400 mt-1">{{ config('journal.subtitle') }}</div>
            </a>
            <div class="w-full sm:max-w-lg bg-white rounded-lg border border-gray-200 p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
