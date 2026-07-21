<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Уведомления</h2>
        <p class="mt-1 text-sm text-gray-600">Настройте получение уведомлений об изменении статуса статей и новых сообщениях.</p>
    </header>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <input type="hidden" name="email" value="{{ $user->email }}">
        <input type="hidden" name="last_name" value="{{ $user->profile->last_name ?? '' }}">
        <input type="hidden" name="first_name" value="{{ $user->profile->first_name ?? '' }}">

        <div class="space-y-3">
            <h3 class="text-sm font-medium text-gray-700">Изменение статуса статей</h3>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="notification_status_changes" value="1"
                    class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary"
                    {{ ($user->notification_preferences['status_changes_enabled'] ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">Все уведомления об изменении статуса</span>
            </label>

            <label class="flex items-center gap-2 ml-6">
                <input type="checkbox" name="notification_email_status" value="1"
                    class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary"
                    {{ ($user->notification_preferences['email_status_changes'] ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">В том числе email-уведомления</span>
            </label>
        </div>

        <div class="space-y-3">
            <h3 class="text-sm font-medium text-gray-700">Обсуждения</h3>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="notification_email_discussions" value="1"
                    class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary"
                    {{ ($user->notification_preferences['email_discussions'] ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">Email-уведомления о новых сообщениях в обсуждениях</span>
            </label>

            <label class="flex items-center gap-2">
                <input type="checkbox" name="notification_site_discussions" value="1"
                    class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary"
                    {{ ($user->notification_preferences['site_discussions'] ?? true) ? 'checked' : '' }}>
                <span class="text-sm text-gray-700">Уведомления на сайте о новых сообщениях</span>
            </label>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Сохранить</x-primary-button>

            @if (session('status') === 'notification-updated')
                <p class="text-sm text-green-600">Настройки уведомлений сохранены.</p>
            @endif
        </div>
    </form>
</section>
