<x-guest-layout>
    <div class="mb-5 text-sm text-gray-600">
        Это защищённая область. Пожалуйста, подтвердите пароль перед продолжением.
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" value="Пароль" />
            <x-text-input id="password" class="mt-1.5 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button>Подтвердить</x-primary-button>
        </div>
    </form>
</x-guest-layout>
