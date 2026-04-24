<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Grade;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $grades = Grade::all();

        if ($grades->isEmpty()) {
            $this->command?->warn('No hay grados registrados. No se crearon cursos.');
            return;
        }

        $courseNumbers = [1, 2, 3];

        foreach ($grades as $grade) {
            foreach ($courseNumbers as $courseNumber) {
                Course::updateOrCreate(
                    [
                        'school_id' => $grade->school_id,
                        'grade_id' => $grade->id,
                        'name' => (string) $courseNumber,
                    ],
                    [
                        'label' => $grade->name . ' - ' . $courseNumber,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}