<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * PURPOSE: Edits a user in the admin panel; if the admin changes the
 * email address, verification is reset and a new verification email
 * is sent to the new address.
 *
 * SPECIFICATION: SPEC-23/AC-8
 */
class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /**
     * PURPOSE: Resets verification and re-sends the verification email
     * when the admin changed the user's email address.
     *
     * SPECIFICATION: SPEC-23/AC-8
     */
    protected function afterSave(): void
    {
        if (! $this->record->wasChanged('email')) {
            return;
        }

        $this->record->forceFill(['email_verified_at' => null])->saveQuietly();
        $this->record->sendEmailVerificationNotification();
    }
}
