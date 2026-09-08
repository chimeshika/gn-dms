<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var array<string, array<int, string>>
     */
    protected array $permissionsByRole = [
        UserRole::Officer->value => [
            'view own profile',
            'view own documents',
        ],
        UserRole::DivisionalAdmin->value => [
            'view officers',
            'create officers',
            'edit officers',
            'verify officers',
            'view service histories',
            'create service histories',
            'generate letters',
            'finalize letters',
            'manage batches',
        ],
        UserRole::DistrictAdmin->value => [
            'view officers',
            'create officers',
            'edit officers',
            'verify officers',
            'view service histories',
            'create service histories',
            'generate letters',
            'finalize letters',
            'manage batches',
            'manage signatories',
        ],
        UserRole::MainAdmin->value => [
            'view officers',
            'create officers',
            'edit officers',
            'delete officers',
            'verify officers',
            'view service histories',
            'create service histories',
            'generate letters',
            'finalize letters',
            'manage batches',
            'manage signatories',
            'manage users',
            'manage districts',
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = collect($this->permissionsByRole)->flatten()->unique();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($this->permissionsByRole as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($perms);
        }
    }
}
