@props(['steps'])

<ol class="flex w-full overflow-x-auto pb-1">
    @foreach($steps as $step)
        <li class="flex-1 min-w-[96px]">
            <div class="flex items-center">
                <span class="w-6 h-6 shrink-0 rounded-full flex items-center justify-center text-[10px] font-bold {{ $step['dotClass'] }}">
                    @if($step['icon'] === 'check')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    @elseif($step['icon'] === 'cross')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>
                @unless($loop->last)
                    <span class="flex-1 h-0.5 mx-1 {{ $step['connectorClass'] }}"></span>
                @endunless
            </div>
            <div class="mt-2 pr-3">
                <div class="text-xs font-medium leading-tight {{ $step['titleClass'] }}">{{ $step['label'] }}</div>
                @if($step['date'])
                    <div class="text-[10px] text-gray-400">{{ $step['date']->format('d.m.Y') }}</div>
                @endif
            </div>
        </li>
    @endforeach
</ol>
