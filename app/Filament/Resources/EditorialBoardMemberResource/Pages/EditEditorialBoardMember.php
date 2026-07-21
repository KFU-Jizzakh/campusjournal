<?php

namespace App\Filament\Resources\EditorialBoardMemberResource\Pages;

use App\Filament\Resources\EditorialBoardMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEditorialBoardMember extends EditRecord
{
    protected static string $resource = EditorialBoardMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
