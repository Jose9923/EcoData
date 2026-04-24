<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::first();

        if (! $school) {
            $school = School::create([
                'name' => 'EcoData Demo',
                'slug' => 'ecodata-demo',
                'primary_color' => '#1d4ed8',
                'secondary_color' => '#0f172a',
                'accent_color' => '#22c55e',
                'is_active' => true,
            ]);
        }

        $grade = Grade::firstOrCreate(
            [
                'school_id' => $school->id,
                'name' => '6',
            ],
            [
                'label' => 'Grado 6',
                'is_active' => true,
            ]
        );

        $course = Course::firstOrCreate(
            [
                'school_id' => $school->id,
                'grade_id' => $grade->id,
                'name' => '6-1',
            ],
            [
                'label' => 'Curso 6-1',
                'is_active' => true,
            ]
        );

        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@ecodata.test'],
            [
                'name' => 'Super Admin',
                'document_type' => 'CC',
                'document_number' => '1234567890',
                'password' => Hash::make('1234567890'),
                'school_id' => $school->id,
                'grade_id' => null,
                'course_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->syncRoles(['super_admin']);

        $schoolAdmin = User::updateOrCreate(
            ['email' => 'admin.colegio@ecodata.test'],
            [
                'name' => 'Admin Colegio',
                'document_type' => 'CC',
                'document_number' => '2234567890',
                'password' => Hash::make('1234567890'),
                'school_id' => $school->id,
                'grade_id' => null,
                'course_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $schoolAdmin->syncRoles(['admin_colegio']);

        $teacher = User::updateOrCreate(
            ['email' => 'teacher@ecodata.test'],
            [
                'name' => 'Docente Demo',
                'document_type' => 'CC',
                'document_number' => '3234567890',
                'password' => Hash::make('1234567890'),
                'school_id' => $school->id,
                'grade_id' => null,
                'course_id' => null,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $teacher->syncRoles(['docente']);

        $student = User::updateOrCreate(
            ['email' => 'student@ecodata.test'],
            [
                'name' => 'Estudiante Demo',
                'document_type' => 'TI',
                'document_number' => '4234567890',
                'password' => Hash::make('1234567890'),
                'school_id' => $school->id,
                'grade_id' => $grade->id,
                'course_id' => $course->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $student->syncRoles(['estudiante']);
    }
}