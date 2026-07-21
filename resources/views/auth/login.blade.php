<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="{{ __('auth.email') }}" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password" value="{{ __('auth.password') }}" />
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('auth.remember') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-gray-500 hover:text-primary transition" href="{{ route('password.request') }}">
                    {{ __('auth.forgot_password') }}
                </a>
            @endif
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('register') }}" class="text-sm text-gray-500 hover:text-primary transition">{{ __('auth.register') }}</a>
            <x-primary-button>{{ __('auth.login') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
