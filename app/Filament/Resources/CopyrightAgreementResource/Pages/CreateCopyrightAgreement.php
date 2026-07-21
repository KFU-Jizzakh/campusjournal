<?php

namespace App\Filament\Resources\CopyrightAgreementResource\Pages;

use App\Filament\Resources\CopyrightAgreementResource;
use App\Models\CopyrightAgreement;
use Filament\Resources\Pages\CreateRecord;

class CreateCopyrightAgreement extends CreateRecord
{
    protected static string $resource = CopyrightAgreementResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['version'] = CopyrightAgreement::nextVersion();

        if ($data['is_active'] ?? false) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->is_active) {
            $this->record->activate();
        }
    }
}
