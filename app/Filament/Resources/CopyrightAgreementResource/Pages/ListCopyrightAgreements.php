<?php

namespace App\Filament\Resources\CopyrightAgreementResource\Pages;

use App\Filament\Resources\CopyrightAgreementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCopyrightAgreements extends ListRecords
{
    protected static string $resource = CopyrightAgreementResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
