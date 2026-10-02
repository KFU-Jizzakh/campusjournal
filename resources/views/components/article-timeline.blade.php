@props(['steps'])

<ol class="flex w-full overflow-x-auto pb-1">
    @foreach($steps as $step)
        <li class="flex-1 min-w-[96px]">
            <div class="flex items-center">
                <span class="w-6 h-6 shrink-0 rounded-full flex items-center justify-center text-[10px] font-bold
                    {{ match($step['state']) {
                        'done' => 'bg-green-500 text-white',
                        'current' => 'bg-blue-600 text-white ring-4 ring-blue-100',
                        'rejected' => 'bg-red-500 text-white',
                        'cancelled' => 'bg-gray-100 text-gray-300',
                        default => 'bg-gray-200 text-gray-400',
                    } }}">
                    @if($step['state'] === 'done')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    @elseif($step['state'] === 'rejected')
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>
                @unless($loop->last)
                    <span class="flex-1 h-0.5 mx-1 {{ in_array($step['state'], ['done', 'rejected'], true) ? 'bg-green-400' : 'bg-gray-200' }}"></span>
                @endunless
            </div>
            <div class="mt-2 pr-3">
                <div class="text-xs font-medium leading-tight {{ $step['state'] === 'current' ? 'text-gray-900' : ($step['state'] === 'cancelled' ? 'text-gray-300' : 'text-gray-500') }}">{{ $step['label'] }}</div>
                @if($step['date'])
                    <div class="text-[10px] text-gray-400">{{ $step['date']->format('d.m.Y') }}</div>
                @endif
            </div>
        </li>
    @endforeach
</ol>
