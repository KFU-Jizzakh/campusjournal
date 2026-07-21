<?php

namespace App\Console\Commands;

use App\Mail\ReviewReminderMailable;
use App\Models\Review;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * PURPOSE: Sends email reminders to reviewers with overdue
 * response or approaching/overdue review deadlines.
 *
 * SPECIFICATION: SPEC-02/AC-3, SPEC-03/AC-2
 */
class SendReviewReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder emails for overdue or approaching deadline reviews';

    /**
     * Minimum days between reminders to avoid spamming
     */
    protected int $minDaysBetweenReminders = 3;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for reviews requiring reminders...');

        $sentCount = 0;

        // 1. Response overdue reminders (status = pending, response_due_at passed)
        $responseOverdue = Review::responseOverdue()
            ->where(function ($query) {
                $query->whereNull('reminded_at')
                    ->orWhere('reminded_at', '<', now()->subDays($this->minDaysBetweenReminders));
            })
            ->with(['reviewer', 'article'])
            ->get();

        $this->info("Found {$responseOverdue->count()} reviews with overdue response.");

        foreach ($responseOverdue as $review) {
            if ($this->sendReminder($review, true)) {
                $sentCount++;
            }
        }

        // 2. Review deadline reminders (status = pending or in_progress, review_due_at approaching or passed)
        $reviewOverdue = Review::overdue()
            ->where(function ($query) {
                $query->whereNull('reminded_at')
                    ->orWhere('reminded_at', '<', now()->subDays($this->minDaysBetweenReminders));
            })
            ->with(['reviewer', 'article'])
            ->get();

        $this->info("Found {$reviewOverdue->count()} reviews with overdue or approaching deadline.");

        foreach ($reviewOverdue as $review) {
            // Skip if we already sent a response reminder for this review today
            if ($responseOverdue->contains('id', $review->id)) {
                continue;
            }

            if ($this->sendReminder($review, false)) {
                $sentCount++;
            }
        }

        $this->info("Sent {$sentCount} reminder emails.");

        return self::SUCCESS;
    }

    /**
     * Send a reminder email for a review
     */
    protected function sendReminder(Review $review, bool $isResponseReminder): bool
    {
        if (! $review->reviewer?->email) {
            $this->warn("Review {$review->id}: No email for reviewer.");

            return false;
        }

        try {
            Mail::to($review->reviewer->email)->queue(new ReviewReminderMailable($review, $isResponseReminder));

            // Update reminded_at timestamp
            $review->update(['reminded_at' => now()]);

            $this->info("Sent reminder to {$review->reviewer->email} for review {$review->id}");

            return true;
        } catch (\Exception $e) {
            $this->error("Failed to send reminder for review {$review->id}: {$e->getMessage()}");

            return false;
        }
    }
}
