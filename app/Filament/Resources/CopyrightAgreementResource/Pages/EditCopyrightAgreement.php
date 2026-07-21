<?php

namespace App\Filament\Resources\CopyrightAgreementResource\Pages;

use App\Filament\Resources\CopyrightAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCopyrightAgreement extends EditRecord
{
    protected static string $resource = CopyrightAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(fn () => $this->record->agreements()->exists()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['is_active'] ?? false) && ! $this->record->is_active) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->is_active) {
            $this->record->activate();
        }
    }
}
