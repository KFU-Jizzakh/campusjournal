<x-guest-layout>
    <div class="mb-5 text-sm text-gray-600">
        Спасибо за регистрацию! Подтвердите адрес электронной почты, перейдя по ссылке в письме. Если вы не получили письмо, мы отправим его повторно.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            Новая ссылка для подтверждения отправлена на ваш email.
        </div>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>Отправить повторно</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-primary transition">Выйти</button>
        </form>
    </div>
</x-guest-layout>
