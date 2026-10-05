<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                <div class="text-2xl font-bold text-gray-900">{{ $queuedJobsCount }}</div>
                <div class="text-sm text-gray-500 mt-1">Задач в очереди</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                <div class="text-2xl font-bold {{ $failedJobsCount > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $failedJobsCount }}</div>
                <div class="text-sm text-gray-500 mt-1">Неудачных задач</div>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                <div class="text-2xl font-bold text-gray-900">
                    {{ $remindersLastRunAt?->format('d.m.Y H:i') ?? 'Нет данных' }}
                </div>
                <div class="text-sm text-gray-500 mt-1">Напоминания рецензентам (последний запуск)</div>
            </div>
        </div>

        <x-filament::section heading="Неудачные задачи (последние 20)">
            @if($failedJobs->isEmpty())
                <p class="text-sm text-gray-500">Неудачных задач нет.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="py-2 pr-4 font-medium">UUID</th>
                            <th class="py-2 pr-4 font-medium">Очередь</th>
                            <th class="py-2 pr-4 font-medium">Ошибка</th>
                            <th class="py-2 font-medium">Время</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($failedJobs as $job)
                            <tr>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-600">{{ $job['uuid'] }}</td>
                                <td class="py-2 pr-4 text-gray-600">{{ $job['queue'] }}</td>
                                <td class="py-2 pr-4 text-red-600">{{ $job['exceptionLine'] }}</td>
                                <td class="py-2 text-gray-400 text-xs">{{ $job['failedAt'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>

        <x-filament::section heading="Crossref-депозиты (последние 10)">
            @if($deposits->isEmpty())
                <p class="text-sm text-gray-500">Депозитов пока не было.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase border-b border-gray-100">
                            <th class="py-2 pr-4 font-medium">DOI</th>
                            <th class="py-2 pr-4 font-medium">Статус</th>
                            <th class="py-2 pr-4 font-medium">HTTP</th>
                            <th class="py-2 pr-4 font-medium">Инициатор</th>
                            <th class="py-2 font-medium">Время</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($deposits as $deposit)
                            <tr>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-600">{{ $deposit->doi }}</td>
                                <td class="py-2 pr-4">
                                    <span class="text-xs px-2 py-0.5 rounded-full
                                        {{ $deposit->statusColor() === 'success' ? 'bg-green-50 text-green-700' : '' }}
                                        {{ $deposit->statusColor() === 'danger' ? 'bg-red-50 text-red-700' : '' }}
                                        {{ $deposit->statusColor() === 'info' ? 'bg-blue-50 text-blue-700' : '' }}
                                        {{ $deposit->statusColor() === 'gray' ? 'bg-gray-100 text-gray-600' : '' }}">
                                        {{ $deposit->statusLabel() }}
                                    </span>
                                </td>
                                <td class="py-2 pr-4 text-gray-600">{{ $deposit->http_status ?? '—' }}</td>
                                <td class="py-2 pr-4 text-gray-600">{{ $deposit->attemptedBy?->email ?? '—' }}</td>
                                <td class="py-2 text-gray-400 text-xs">{{ $deposit->created_at?->format('d.m.Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
