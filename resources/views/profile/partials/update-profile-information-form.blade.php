<section>
    <h3 class="font-semibold text-gray-900 mb-1">{{ __('profile.personal_data') }}</h3>
    <p class="text-sm text-gray-500 mb-4">{{ __('profile.personal_data_hint') }}</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <x-input-label for="last_name" value="{{ __('profile.last_name') }}" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $user->profile?->last_name)" required autocomplete="family-name" />
                <x-input-error class="mt-1" :messages="$errors->get('last_name')" />
            </div>
            <div>
                <x-input-label for="first_name" value="{{ __('profile.first_name') }}" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $user->profile?->first_name)" required autocomplete="given-name" />
                <x-input-error class="mt-1" :messages="$errors->get('first_name')" />
            </div>
            <div>
                <x-input-label for="middle_name" value="{{ __('profile.patronymic') }}" />
                <x-text-input id="middle_name" name="middle_name" type="text" class="mt-1 block w-full" :value="old('middle_name', $user->profile?->middle_name)" autocomplete="additional-name" />
                <x-input-error class="mt-1" :messages="$errors->get('middle_name')" />
            </div>
        </div>

        <div>
            <x-input-label for="email" value="{{ __('profile.email') }}" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />
            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-gray-600">
                        {{ __('auth.email_unverified') }}
                        <button form="send-verification" class="text-primary hover:underline">{{ __('auth.resend_verification') }}</button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 text-sm text-green-600">{{ __('auth.verification_sent') }}</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="affiliation" value="{{ __('profile.affiliation') }}" />
                <x-text-input id="affiliation" name="affiliation" type="text" class="mt-1 block w-full" :value="old('affiliation', $user->profile?->affiliation)" autocomplete="organization" />
                <x-input-error class="mt-1" :messages="$errors->get('affiliation')" />
            </div>
            <div>
                <x-input-label for="country" value="{{ __('profile.country') }}" />
                <select id="country" name="country" class="mt-1 block w-full border-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm text-sm">
                    <option value="">{{ __('profile.select_country') }}</option>
                    @foreach ($countries as $country)
                        <option value="{{ $country->value }}" {{ old('country', $user->profile?->country) === $country->value ? 'selected' : '' }}>{{ $country->value }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-1" :messages="$errors->get('country')" />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="orcid" value="{{ __('profile.orcid') }}" />
                <x-text-input id="orcid" name="orcid" type="text" class="mt-1 block w-full" :value="old('orcid', $user->profile?->orcid)" placeholder="{{ __('profile.orcid_placeholder') }}" />
                <x-input-error class="mt-1" :messages="$errors->get('orcid')" />
            </div>
            <div>
                <x-input-label for="phone" value="{{ __('profile.phone') }}" />
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->profile?->phone)" autocomplete="tel" />
                <x-input-error class="mt-1" :messages="$errors->get('phone')" />
            </div>
        </div>

        <div>
            <x-input-label for="url" value="{{ __('profile.website') }}" />
            <x-text-input id="url" name="url" type="url" class="mt-1 block w-full" :value="old('url', $user->profile?->url)" placeholder="{{ __('profile.website_placeholder') }}" />
            <x-input-error class="mt-1" :messages="$errors->get('url')" />
        </div>

        <div>
            <x-input-label for="bio" value="{{ __('profile.about') }}" />
            <textarea id="bio" name="bio" rows="3" class="mt-1 block w-full border-gray-300 focus:border-primary focus:ring-primary rounded-md shadow-sm text-sm">{{ old('bio', $user->profile?->bio) }}</textarea>
            <x-input-error class="mt-1" :messages="$errors->get('bio')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('profile.save') }}</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600">{{ __('profile.saved') }}</p>
            @endif
        </div>
    </form>
</section>
