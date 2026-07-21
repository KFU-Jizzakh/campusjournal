<?php

namespace App\Traits;

/**
 * PURPOSE: Provides channel resolution for status-change notifications
 * based on per-user notification preferences.
 */
trait HasNotificationChannels
{
    public function via(object $notifiable): array
    {
        $channels = [];
        $prefs = $notifiable->notification_preferences ?? [];

        if (! ($prefs['status_changes_enabled'] ?? true)) {
            return [];
        }

        $channels[] = 'database';

        if ($prefs['email_status_changes'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
