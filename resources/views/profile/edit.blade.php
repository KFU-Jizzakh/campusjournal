<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('nav.profile') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            @include('profile.partials.update-password-form')
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            @include('profile.partials.update-notification-settings')
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
