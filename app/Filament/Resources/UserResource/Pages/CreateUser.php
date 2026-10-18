<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * PURPOSE: Creates a user in the admin panel and sends them a
 * verification email unless they were created already verified.
 */
class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * PURPOSE: Sends a verification email to the new user unless the admin
     * created them as already verified.
     */
    protected function afterCreate(): void
    {
        if (! $this->record->hasVerifiedEmail()) {
            $this->record->sendEmailVerificationNotification();
        }
    }
}
