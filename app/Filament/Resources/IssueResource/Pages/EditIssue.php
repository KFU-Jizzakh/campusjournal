<?php

namespace App\Filament\Resources\IssueResource\Pages;

use App\Filament\Resources\IssueResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditIssue extends EditRecord
{
    protected static string $resource = IssueResource::class;

    /**
     * PURPOSE: Block unpublishing an issue that still contains
     * published or retracted articles, showing a notification
     * instead of saving.
     *
     * SPECIFICATION: SPEC-24/AC-5, SPEC-24/BR-1
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $issue = $this->record;

        if (
            $issue->getOriginal('status') === 'published'
            && ($data['status'] ?? null) !== 'published'
            && $issue->hasPublishedArticles()
        ) {
            Notification::make()
                ->danger()
                ->title(__('issue.error_unpublish_with_published_articles'))
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
