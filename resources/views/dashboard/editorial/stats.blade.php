<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.stats.heading') }}</h2>
    </x-slot>

    <div class="space-y-6">
        {{-- Key metrics --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <div class="text-2xl font-bold text-primary">
                    {{ $avgDaysToDecision !== null ? $avgDaysToDecision.' '.__('dashboard.stats.days') : '—' }}
                </div>
                <div class="text-sm text-gray-500 mt-1">{{ __('dashboard.stats.avg_decision') }}</div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-5">
                <div class="text-2xl font-bold text-primary">
                    {{ $avgTurnaround !== null ? $avgTurnaround.' '.__('dashboard.stats.days') : '—' }}
                </div>
                <div class="text-sm text-gray-500 mt-1">{{ __('dashboard.stats.avg_turnaround') }}</div>
            </div>
        </div>

        {{-- Pipeline funnel --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('dashboard.stats.funnel') }}</h3>
            <div class="space-y-3">
                @foreach($funnel as $row)
                    <div class="flex items-center gap-3">
                        <div class="w-40 shrink-0 text-sm text-gray-600 truncate">{{ $row['label'] }}</div>
                        <div class="flex-1 h-5 bg-gray-100 rounded overflow-hidden">
                            <div class="h-5 bg-primary/70 rounded" style="width: {{ $row['percent'] }}%"></div>
                        </div>
                        <div class="w-10 shrink-0 text-sm font-medium text-gray-900 text-right">{{ $row['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Section editor workload --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="p-5 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900">{{ __('dashboard.stats.section_load') }}</h3>
            </div>
            @if(count($sectionLoad) === 0)
                <div class="p-8 text-center text-gray-400 text-sm">{{ __('dashboard.stats.no_editors') }}</div>
            @else
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-50">
                        @foreach($sectionLoad as $row)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-3 font-medium text-gray-900">{{ $row['user']->full_name }}</td>
                                <td class="px-5 py-3 text-gray-500 text-right">{{ __('dashboard.stats.active_articles', ['count' => $row['active']]) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</x-app-layout>
