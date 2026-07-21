<?php

namespace App\Filament\Resources\EditorialBoardMemberResource\Pages;

use App\Filament\Resources\EditorialBoardMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEditorialBoardMembers extends ListRecords
{
    protected static string $resource = EditorialBoardMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
