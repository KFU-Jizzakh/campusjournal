<section>
    <h3 class="font-semibold text-gray-900 mb-1">{{ __('profile.delete_account') }}</h3>
    <p class="text-sm text-gray-500 mb-4">{{ __('profile.delete_account_hint') }}</p>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">{{ __('profile.delete_account_button') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-semibold text-gray-900">{{ __('profile.confirm_delete_title') }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ __('profile.confirm_delete_hint') }}</p>

            <div class="mt-4">
                <x-input-label for="password" value="{{ __('profile.password_label') }}" class="sr-only" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" placeholder="{{ __('profile.password_placeholder') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
            </div>

            <div class="mt-4 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('profile.cancel') }}</x-secondary-button>
                <x-danger-button>{{ __('profile.delete') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
