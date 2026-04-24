<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\School;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::all();

        if ($schools->isEmpty()) {
            $this->command?->warn('No hay colegios registrados. No se crearon grados.');
            return;
        }

        $grades = [6, 7, 8, 9, 10, 11];

        foreach ($schools as $school) {
            foreach ($grades as $gradeNumber) {
                Grade::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'name' => (string) $gradeNumber,
                    ],
                    [
                        'label' => (string) $gradeNumber,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}