@props(['items'])

<ul class="space-y-2.5">
    @foreach($items as $item)
        <li class="flex items-center gap-2.5 text-sm {{ $item['rowClass'] }}">
            @if($item['done'])
                <span class="w-4 h-4 shrink-0 rounded-full bg-green-500 text-white flex items-center justify-center">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                </span>
            @else
                <span class="w-4 h-4 shrink-0 rounded-full border border-gray-300 inline-block"></span>
            @endif
            <span>{{ $item['label'] }}</span>
            @if($item['skipped'])
                <span class="text-[10px]">({{ __('dashboard.checklist.not_required') }})</span>
            @endif
        </li>
    @endforeach
</ul>
