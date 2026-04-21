<?php

namespace Database\Seeders;

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

        $user = User::updateOrCreate(
            ['email' => 'admin@ecodata.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin123*'),
                'school_id' => $school->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles(['super_admin']);
    }
}