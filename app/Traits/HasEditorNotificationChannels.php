<?php

namespace App\Traits;

/**
 * PURPOSE: Provides channel resolution for editor-facing notifications.
 * Always delivers via both database and mail — editor notifications
 * are operational and not subject to author-facing preferences.
 */
trait HasEditorNotificationChannels
{
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }
}
