<?php

namespace App\Filament\Pages;

use App\Models\CrossrefDeposit;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PURPOSE: Admin-only operational health overview — queue depth, failed
 * jobs, Crossref deposit outcomes, and the review-reminder schedule log.
 */
class SystemHealth extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Здоровье системы';

    protected static ?string $title = 'Здоровье системы';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 13;

    protected string $view = 'filament.pages.system-health';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    protected function getViewData(): array
    {
        $reminderLog = storage_path('logs/schedule-reviews-reminders.log');

        return [
            'queuedJobsCount' => DB::table('jobs')->count(),
            'failedJobsCount' => DB::table('failed_jobs')->count(),
            'failedJobs' => DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit(20)
                ->get(['uuid', 'queue', 'exception', 'failed_at'])
                ->map(fn (object $job) => [
                    'uuid' => $job->uuid,
                    'queue' => $job->queue,
                    'exceptionLine' => strtok($job->exception, "\n") ?: '—',
                    'failedAt' => $job->failed_at,
                ]),
            'deposits' => CrossrefDeposit::with('attemptedBy:id,email')
                ->latest()
                ->limit(10)
                ->get(['id', 'doi', 'batch_id', 'status', 'http_status', 'attempted_by', 'created_at']),
            'remindersLastRunAt' => file_exists($reminderLog)
                ? Carbon::createFromTimestamp(filemtime($reminderLog))
                : null,
        ];
    }
}
