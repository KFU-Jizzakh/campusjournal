<x-guest-layout>
    <div class="mb-5 text-sm text-gray-600">
        Укажите email, и мы отправим ссылку для сброса пароля.
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-1.5 block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-primary transition">Назад</a>
            <x-primary-button>Отправить ссылку</x-primary-button>
        </div>
    </form>
</x-guest-layout>
