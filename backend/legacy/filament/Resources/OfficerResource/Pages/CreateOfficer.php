<?php

namespace App\Filament\Resources\OfficerResource\Pages;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\OfficerResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateOfficer extends CreateRecord
{
    protected static string $resource = OfficerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = User::create([
            'name' => $data['full_name_en'] ?? $data['nic_no'],
            'email' => Str::lower($data['nic_no']).'@gn.gov.lk',
            'password' => Str::password(),
            'nic_no' => $data['nic_no'],
            'role' => UserRole::Officer,
            'status' => UserStatus::Active,
            'ds_division_id' => $data['current_ds_division_id'] ?? null,
            'district_id' => $data['current_district_id'] ?? null,
        ]);

        $user->syncRoles([UserRole::Officer->spatieRole()]);

        $data['user_id'] = $user->id;

        return $data;
    }
}
