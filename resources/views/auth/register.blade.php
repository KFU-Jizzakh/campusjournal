<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="last_name" value="{{ __('auth.last_name') }}" />
                <x-text-input id="last_name" class="mt-1.5 block w-full" type="text" name="last_name" :value="old('last_name')" required autofocus autocomplete="family-name" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="first_name" value="{{ __('auth.first_name') }}" />
                <x-text-input id="first_name" class="mt-1.5 block w-full" type="text" name="first_name" :value="old('first_name')" required autocomplete="given-name" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="middle_name" value="{{ __('auth.patronymic') }}" />
                <x-text-input id="middle_name" class="mt-1.5 block w-full" type="text" name="middle_name" :value="old('middle_name')" autocomplete="additional-name" />
                <x-input-error :messages="$errors->get('middle_name')" class="mt-1.5" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="{{ __('auth.email') }}" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="affiliation" value="{{ __('auth.affiliation') }}" />
                <x-text-input id="affiliation" class="mt-1.5 block w-full" type="text" name="affiliation" :value="old('affiliation')" autocomplete="organization" />
                <x-input-error :messages="$errors->get('affiliation')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="country" value="{{ __('auth.country') }}" />
                <select id="country" name="country" class="mt-1.5 block w-full border-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm text-sm">
                    <option value="">{{ __('auth.select_country') }}</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->value }}" {{ old('country') === $country->value ? 'selected' : '' }}>
                            {{ $country->value }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('country')" class="mt-1.5" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="password" value="{{ __('auth.password') }}" />
                <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="{{ __('auth.password_confirmation') }}" />
                <x-text-input id="password_confirmation" class="mt-1.5 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5" />
            </div>
        </div>

        <div>
            <label class="flex items-start gap-2">
                <input id="privacy" type="checkbox" name="privacy" value="1" class="mt-1 rounded border-gray-300 text-primary shadow-sm focus:ring-primary" {{ old('privacy') ? 'checked' : '' }} />
                <span class="text-sm text-gray-600">{{ __('auth.privacy_consent') }}</span>
            </label>
            <x-input-error :messages="$errors->get('privacy')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm text-gray-500 hover:text-primary transition" href="{{ route('login') }}">{{ __('auth.already_registered') }}</a>
            <x-primary-button>{{ __('auth.register_button') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
