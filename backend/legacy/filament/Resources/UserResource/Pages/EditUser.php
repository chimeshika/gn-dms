<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        $role = UserRole::tryFrom($this->record->role->value);

        if ($role) {
            $this->record->syncRoles([$role->spatieRole()]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
