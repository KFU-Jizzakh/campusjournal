@props(['item'])

<div class="flex flex-col sm:flex-row sm:items-center gap-3 px-5 py-4 border-l-2 {{ $item->stripeClass() }}">
    <div class="min-w-0 flex-1">
        <div class="font-medium text-gray-900 truncate">{{ $item->title }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ $item->task }}</div>
        <div class="flex flex-wrap items-center gap-2 mt-1.5">
            @if($item->deadlineLabel)
                <x-status-badge :color="$item->urgencyColor()" :label="$item->deadlineLabel" />
            @endif
            @if($item->badgeLabel)
                <x-status-badge :color="$item->badgeColor ?? 'gray'" :label="$item->badgeLabel" />
            @endif
        </div>
    </div>
    <div class="shrink-0 flex items-center gap-3">
        @if($item->primaryForm)
            <form method="POST" action="{{ $item->primaryForm->url }}" class="inline">
                @csrf
                <button type="submit" class="text-sm text-green-600 hover:text-green-800 font-medium">{{ $item->primaryForm->label }}</button>
            </form>
            @if($item->secondaryForm)
                <form method="POST" action="{{ $item->secondaryForm->url }}" class="inline" @if($item->secondaryForm->confirm) onsubmit="return confirm({{ \Illuminate\Support\Js::from($item->secondaryForm->confirm) }});" @endif>
                    @csrf
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">{{ $item->secondaryForm->label }}</button>
                </form>
            @endif
            <a href="{{ $item->url }}" class="text-sm text-primary hover:underline">{{ $item->actionLabel }}</a>
        @else
            <a href="{{ $item->url }}" class="text-sm text-primary hover:underline">{{ $item->actionLabel }}</a>
        @endif
    </div>
</div>
