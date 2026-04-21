<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Panel
            'dashboard.view',

            // Colegios
            'schools.manage',

            // Usuarios
            'users.manage',
            'users.import',

            // Grados y cursos
            'grades.manage',
            'courses.manage',

            // Guías
            'guides.manage',
            'guides.view',
            'guides.download',

            // Variables físicas
            'physical_variables.manage',
            'physical_records.create',
            'physical_records.view',
            'physical_records.edit',
            'physical_records.delete',

            // Reportes físicos
            'physical_reports.view',
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
            'dashboard.view',
            'users.manage',
            'users.import',
            'grades.manage',
            'courses.manage',
            'guides.manage',
            'guides.view',
            'guides.download',
            'physical_variables.manage',
            'physical_records.create',
            'physical_records.view',
            'physical_records.edit',
            'physical_records.delete',
            'physical_reports.view',
            'physical_reports.export',
        ]);

        $docente->syncPermissions([
            'dashboard.view',
            'guides.manage',
            'guides.view',
            'guides.download',
            'physical_records.create',
            'physical_records.view',
            'physical_reports.view',
        ]);

        $estudiante->syncPermissions([
            'dashboard.view',
            'guides.view',
            'guides.download',
            'physical_records.view',
            'physical_reports.view',
        ]);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}