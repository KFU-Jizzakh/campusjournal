<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">Уведомления</h2>
    </x-slot>

    <div class="space-y-4">
        @if($notifications->isNotEmpty())
            <form method="POST" action="{{ route('notifications.read-all') }}" class="text-right">
                @csrf
                <button type="submit" class="text-sm text-primary hover:underline">Отметить все как прочитанные</button>
            </form>

            <div class="space-y-2">
                @foreach($notifications as $notification)
                    @php
                        $linkRoute = $notification->data['route'] ?? null;
                        $linkParams = $notification->data['route_params'] ?? [];
                        $hasLink = $linkRoute && !empty($linkParams);
                    @endphp
                    <div class="bg-white rounded-lg border border-gray-200 p-4 {{ $notification->read_at ? '' : 'border-l-4 border-l-primary' }}">
                        <div class="flex items-start justify-between">
                            <div>
                                @if($hasLink)
                                    <a href="{{ route($linkRoute, $linkParams) }}" class="text-sm font-medium text-gray-900 hover:underline">
                                        {{ $notification->data['event_description'] ?? $notification->data['author_name'] ?? 'Система' }}
                                    </a>
                                @elseif(isset($notification->data['article_id']))
                                    <a href="{{ route('editorial.show', $notification->data['article_id']) }}" class="text-sm font-medium text-gray-900 hover:underline">
                                        {{ $notification->data['author_name'] ?? 'Система' }}
                                    </a>
                                @else
                                    <span class="text-sm font-medium text-gray-900">{{ $notification->data['author_name'] ?? 'Система' }}</span>
                                @endif
                                <span class="text-xs text-gray-400 ml-2">{{ $notification->created_at->diffForHumans() }}</span>
                            </div>
                            @if(!$notification->read_at)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs text-gray-400 hover:text-primary">Прочитано</button>
                                </form>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ $notification->data['message_preview'] ?? '' }}</p>
                        @if(isset($notification->data['article_title']))
                            <p class="text-xs text-gray-400 mt-1">Статья: {{ $notification->data['article_title'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $notifications->links() }}</div>
        @else
            <div class="bg-white rounded-lg border border-gray-200 p-8 text-center text-sm text-gray-400">
                Нет уведомлений
            </div>
        @endif
    </div>
</x-app-layout>
