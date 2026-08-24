<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $role = UserRole::tryFrom($this->record->role->value);

        if ($role) {
            $this->record->syncRoles([$role->spatieRole()]);
        }
    }
}
