<?php

namespace App\Traits;

/**
 * PURPOSE: Provides channel resolution for discussion notifications
 * based on per-user notification preferences for discussions.
 */
trait HasDiscussionNotificationChannels
{
    public function via(object $notifiable): array
    {
        $channels = [];
        $prefs = $notifiable->notification_preferences ?? [];

        if ($prefs['site_discussions'] ?? true) {
            $channels[] = 'database';
        }

        if ($prefs['email_discussions'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
