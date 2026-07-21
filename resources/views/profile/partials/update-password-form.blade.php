<section>
    <h3 class="font-semibold text-gray-900 mb-1">{{ __('profile.change_password') }}</h3>
    <p class="text-sm text-gray-500 mb-4">{{ __('profile.change_password_hint') }}</p>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="{{ __('profile.current_password') }}" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="update_password_password" value="{{ __('profile.new_password') }}" />
                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="update_password_password_confirmation" value="{{ __('profile.password_confirmation') }}" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('profile.save') }}</x-primary-button>
            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-green-600">{{ __('profile.saved') }}</p>
            @endif
        </div>
    </form>
</section>
