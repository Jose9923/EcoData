<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'schools.manage',
            'users.manage',
            'users.import',
            'guides.manage',
            'guides.download',
            'physical_variables.manage',
            'physical_records.create',
            'physical_reports.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $adminColegio = Role::firstOrCreate(['name' => 'admin_colegio']);
        $docente = Role::firstOrCreate(['name' => 'docente']);
        $estudiante = Role::firstOrCreate(['name' => 'estudiante']);

        $superAdmin->syncPermissions($permissions);

        $adminColegio->syncPermissions([
            'users.manage',
            'users.import',
            'guides.manage',
            'physical_variables.manage',
            'physical_records.create',
            'physical_reports.export',
        ]);

        $docente->syncPermissions([
            'guides.manage',
            'physical_records.create',
            'physical_reports.export',
        ]);

        $estudiante->syncPermissions([
            'guides.download',
        ]);
    }
}