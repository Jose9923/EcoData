<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        School::firstOrCreate(
            ['slug' => 'ecodata-demo'],
            [
                'name' => 'EcoData Demo',
                'primary_color' => '#1d4ed8',
                'secondary_color' => '#0f172a',
                'accent_color' => '#22c55e',
                'is_active' => true,
            ]
        );
    }
}