<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EcoDataDemoSeeder extends Seeder
{
    private array $schoolIds = [];
    private array $gradeIds = [];
    private array $courseIds = [];
    private array $userIds = [];
    private array $userBySchool = [];
    private array $roleIds = [];
    private array $categoryIds = [];
    private array $variableIds = [];
    private array $recordIds = [];
    private array $weatherStationIds = [];
    private array $fieldDiaryActivityIds = [];
    private array $fieldDiaryQuestionIds = [];
    private array $fieldDiarySubmissionIds = [];

    public function run(): void
    {
        $this->cleanDemoTables();

        $this->seedRolesAndPermissions();
        $this->seedSchools();
        $this->seedGrades();
        $this->seedCourses();
        $this->seedUsers();
        $this->seedPhysicalVariableCategories();
        $this->seedPhysicalVariables();
        $this->seedPhysicalVariableRecords();
        $this->seedLaboratoryGuides();

        /*
        |--------------------------------------------------------------------------
        | Tablas de módulos nuevos
        |--------------------------------------------------------------------------
        | Se ejecutan solo si existen en tu base de datos.
        */
        $this->seedWeatherStations();
        $this->seedSensors();
        $this->seedEnvironmentalEvents();
        $this->seedFieldDiaryActivities();
        $this->seedFieldDiarySubmissions();
    }

    private function cleanDemoTables(): void
    {
        $tables = [
            'field_diary_answers',
            'field_diary_submissions',
            'field_diary_questions',
            'field_diary_activities',

            'environmental_event_acknowledgements',
            'environmental_events',

            'sensors',
            'weather_stations',

            'laboratory_guides',

            'physical_variable_record_values',
            'physical_variable_records',
            'physical_variables',
            'physical_variable_categories',

            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',

            'users',
            'courses',
            'grades',
            'schools',
        ];

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } catch (\Throwable $e) {
            //
        }

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Throwable $e) {
            //
        }
    }

    private function seedRolesAndPermissions(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $roles = [
            'super_admin',
            'admin_colegio',
            'docente',
            'estudiante',
        ];

        foreach ($roles as $role) {
            $this->roleIds[$role] = DB::table('roles')->insertGetId([
                'name' => $role,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissions = [
            'schools.manage',
            'users.manage',
            'grades.manage',
            'courses.manage',
            'variables.manage',
            'records.view',
            'records.create',
            'records.edit',
            'records.export',
            'weather-stations.manage',
            'sensors.manage',
            'laboratory-guides.manage',
            'environmental-events.manage',
            'field-diaries.manage',
            'field-diaries.review',
        ];

        $permissionIds = [];

        foreach ($permissions as $permission) {
            $permissionIds[$permission] = DB::table('permissions')->insertGetId([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('role_has_permissions')) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $this->roleIds['super_admin'],
                ]);
            }
        }
    }

    private function seedSchools(): void
    {
        $palettes = [
            ['#22c55e', '#0f172a', '#86efac'],
            ['#1d4ed8', '#0f172a', '#22c55e'],
            ['#7c3aed', '#1e1b4b', '#facc15'],
            ['#dc2626', '#111827', '#f97316'],
            ['#0891b2', '#164e63', '#a7f3d0'],
            ['#16a34a', '#14532d', '#fbbf24'],
            ['#9333ea', '#312e81', '#fb7185'],
            ['#2563eb', '#1e3a8a', '#38bdf8'],
            ['#ea580c', '#431407', '#facc15'],
            ['#0d9488', '#134e4a', '#99f6e4'],
            ['#be123c', '#4c0519', '#fda4af'],
            ['#4f46e5', '#111827', '#c4b5fd'],
            ['#65a30d', '#1a2e05', '#bef264'],
            ['#0ea5e9', '#082f49', '#fde047'],
            ['#64748b', '#0f172a', '#22c55e'],
        ];

        for ($i = 1; $i <= 15; $i++) {
            $name = "Colegio Demo {$i}";
            $palette = $palettes[$i - 1];

            $this->schoolIds[$i] = DB::table('schools')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name),
                'shield_path' => null,
                'primary_color' => $palette[0],
                'secondary_color' => $palette[1],
                'accent_color' => $palette[2],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedGrades(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $gradeNumber = 5 + $i;

            $this->gradeIds[$i] = DB::table('grades')->insertGetId([
                'school_id' => $this->schoolIds[$i],
                'name' => (string) $gradeNumber,
                'label' => "Grado {$gradeNumber}",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedCourses(): void
    {
        $letters = ['A', 'B', 'C', 'D', 'E'];

        for ($i = 1; $i <= 15; $i++) {
            $letter = $letters[($i - 1) % count($letters)];
            $gradeName = (string) (5 + $i);

            $this->courseIds[$i] = DB::table('courses')->insertGetId([
                'school_id' => $this->schoolIds[$i],
                'grade_id' => $this->gradeIds[$i],
                'name' => $letter,
                'label' => "{$gradeName}{$letter}",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedUsers(): void
    {
        $users = [
            [
                'name' => 'Super Admin Demo',
                'email' => 'superadmin@ecodata.test',
                'role' => 'super_admin',
                'school_index' => null,
                'document_type' => 'CC',
                'document_number' => '90000001',
            ],
            [
                'name' => 'Admin Colegio Demo 1',
                'email' => 'admin1@ecodata.test',
                'role' => 'admin_colegio',
                'school_index' => 1,
                'document_type' => 'CC',
                'document_number' => '90000002',
            ],
            [
                'name' => 'Admin Colegio Demo 2',
                'email' => 'admin2@ecodata.test',
                'role' => 'admin_colegio',
                'school_index' => 2,
                'document_type' => 'CC',
                'document_number' => '90000003',
            ],
            [
                'name' => 'Admin Colegio Demo 3',
                'email' => 'admin3@ecodata.test',
                'role' => 'admin_colegio',
                'school_index' => 3,
                'document_type' => 'CC',
                'document_number' => '90000004',
            ],
        ];

        for ($i = 1; $i <= 5; $i++) {
            $schoolIndex = 3 + $i;

            $users[] = [
                'name' => "Docente Demo {$i}",
                'email' => "docente{$i}@ecodata.test",
                'role' => 'docente',
                'school_index' => $schoolIndex,
                'document_type' => 'CC',
                'document_number' => '9000001' . $i,
            ];
        }

        for ($i = 1; $i <= 6; $i++) {
            $schoolIndex = 9 + $i;

            $users[] = [
                'name' => "Estudiante Demo {$i}",
                'email' => "estudiante{$i}@ecodata.test",
                'role' => 'estudiante',
                'school_index' => $schoolIndex,
                'document_type' => 'TI',
                'document_number' => '9000002' . $i,
            ];
        }

        foreach ($users as $index => $userData) {
            $schoolIndex = $userData['school_index'];

            $schoolId = $schoolIndex ? $this->schoolIds[$schoolIndex] : null;
            $gradeId = $userData['role'] === 'estudiante' && $schoolIndex ? $this->gradeIds[$schoolIndex] : null;
            $courseId = $userData['role'] === 'estudiante' && $schoolIndex ? $this->courseIds[$schoolIndex] : null;

            $userId = DB::table('users')->insertGetId([
                'school_id' => $schoolId,
                'grade_id' => $gradeId,
                'course_id' => $courseId,
                'name' => $userData['name'],
                'email' => $userData['email'],
                'document_type' => $userData['document_type'],
                'document_number' => $userData['document_number'],
                'email_verified_at' => now(),
                'password' => Hash::make('Cambio123*'),
                'remember_token' => Str::random(10),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->userIds[$index + 1] = $userId;

            if ($schoolId && ! isset($this->userBySchool[$schoolId])) {
                $this->userBySchool[$schoolId] = $userId;
            }

            if (Schema::hasTable('model_has_roles') && isset($this->roleIds[$userData['role']])) {
                DB::table('model_has_roles')->insert([
                    'role_id' => $this->roleIds[$userData['role']],
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                ]);
            }
        }
    }

    private function seedPhysicalVariableCategories(): void
    {
        $categories = [
            'Temperatura',
            'Humedad',
            'Presión atmosférica',
            'Precipitación',
            'Viento',
            'Radiación solar',
            'Radiación ultravioleta',
            'Calidad del aire',
            'Gases ambientales',
            'Suelo',
            'Agua',
            'Ruido ambiental',
            'Luminosidad',
            'Energía',
            'Observaciones cualitativas',
        ];

        foreach ($categories as $index => $name) {
            $this->categoryIds[$index + 1] = DB::table('physical_variable_categories')->insertGetId([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => "Categoría demo para variables relacionadas con {$name}.",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedPhysicalVariables(): void
    {
        $variables = [
            ['Temperatura ambiente', 'temperatura-ambiente', '°C', 'decimal', -10, 60, 2],
            ['Humedad relativa', 'humedad-relativa', '%', 'decimal', 0, 100, 2],
            ['Presión atmosférica', 'presion-atmosferica', 'hPa', 'decimal', 800, 1100, 2],
            ['Precipitación acumulada', 'precipitacion-acumulada', 'mm', 'decimal', 0, 300, 2],
            ['Velocidad del viento', 'velocidad-viento', 'm/s', 'decimal', 0, 80, 2],
            ['Dirección del viento', 'direccion-viento', '°', 'integer', 0, 360, 0],
            ['Índice UV', 'indice-uv', 'UV', 'decimal', 0, 15, 2],
            ['CO2 equivalente', 'co2-equivalente', 'ppm', 'integer', 300, 5000, 0],
            ['Compuestos orgánicos volátiles', 'cov', 'ppb', 'integer', 0, 2000, 0],
            ['Humedad del suelo', 'humedad-suelo', '%', 'decimal', 0, 100, 2],
            ['pH del agua', 'ph-agua', 'pH', 'decimal', 0, 14, 2],
            ['Nivel de ruido', 'nivel-ruido', 'dB', 'decimal', 20, 130, 2],
            ['Luminosidad', 'luminosidad', 'lux', 'integer', 0, 100000, 0],
            ['Estado del sensor', 'estado-sensor', null, 'boolean', null, null, 0],
            ['Observación ambiental', 'observacion-ambiental', null, 'text', null, null, 0],
        ];

        foreach ($variables as $index => $variable) {
            $schoolIndex = $index + 1;

            $this->variableIds[$schoolIndex] = DB::table('physical_variables')->insertGetId([
                'school_id' => $this->schoolIds[$schoolIndex],
                'category_id' => $this->categoryIds[$schoolIndex],
                'name' => $variable[0],
                'slug' => $variable[1],
                'unit' => $variable[2],
                'data_type' => $variable[3],
                'min_value' => $variable[4],
                'max_value' => $variable[5],
                'decimals' => $variable[6],
                'description' => "Variable demo {$variable[0]} para pruebas de formularios, filtros y registros.",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedPhysicalVariableRecords(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $schoolId = $this->schoolIds[$i];

            $recordId = DB::table('physical_variable_records')->insertGetId([
                'school_id' => $schoolId,
                'grade_id' => $this->gradeIds[$i],
                'course_id' => $this->courseIds[$i],
                'user_id' => $this->userBySchool[$schoolId] ?? null,
                'recorded_at' => Carbon::now()->subDays(15 - $i)->setTime(7 + ($i % 5), 15),
                'observations' => "Registro físico demo {$i}. Observación generada para validar responsive, detalle y filtros.",
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->recordIds[$i] = $recordId;

            $this->insertRecordValue($recordId, $this->variableIds[$i], $i);
        }
    }

    private function insertRecordValue(int $recordId, int $variableId, int $index): void
    {
        $dataType = DB::table('physical_variables')
            ->where('id', $variableId)
            ->value('data_type');

        $payload = [
            'physical_variable_record_id' => $recordId,
            'physical_variable_id' => $variableId,
            'value_numeric' => null,
            'value_text' => null,
            'value_boolean' => null,
            'value_date' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (in_array($dataType, ['decimal', 'integer', 'number'], true)) {
            $payload['value_numeric'] = match ($index) {
                1 => 24.5,
                2 => 78.2,
                3 => 1012.4,
                4 => 13.7,
                5 => 4.6,
                6 => 180,
                7 => 6.3,
                8 => 740,
                9 => 320,
                10 => 42.5,
                11 => 7.2,
                12 => 65.4,
                13 => 18000,
                default => 20 + $index,
            };
        } elseif ($dataType === 'boolean') {
            $payload['value_boolean'] = $index % 2 === 0;
        } elseif ($dataType === 'date') {
            $payload['value_date'] = now()->subDays($index)->toDateString();
        } else {
            $payload['value_text'] = "Observación demo asociada al registro {$index}.";
        }

        DB::table('physical_variable_record_values')->insert($payload);
    }

    private function seedLaboratoryGuides(): void
    {
        if (! Schema::hasTable('laboratory_guides')) {
            return;
        }

        $this->createDemoPdf();

        for ($i = 1; $i <= 15; $i++) {
            $schoolId = $this->schoolIds[$i];

            DB::table('laboratory_guides')->insert([
                'school_id' => $schoolId,
                'grade_id' => $this->gradeIds[$i],
                'course_id' => $this->courseIds[$i],
                'title' => "Guía de laboratorio ambiental {$i}",
                'description' => "Guía demo para analizar variables ambientales, estación meteorológica y registros físicos.",
                'pdf_path' => 'laboratory-guides/demo-guia-ambiental.pdf',
                'published_at' => now()->subDays(15 - $i),
                'is_active' => true,
                'created_by' => $this->userBySchool[$schoolId] ?? $this->userIds[1],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedWeatherStations(): void
    {
        if (! Schema::hasTable('weather_stations')) {
            return;
        }

        for ($i = 1; $i <= 15; $i++) {
            $schoolId = $this->schoolIds[$i];

            $payload = [
                'school_id' => $schoolId,
                'responsible_user_id' => $this->userBySchool[$schoolId] ?? null,
                'name' => "Estación Meteorológica Demo {$i}",
                'code' => 'EMD-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'location' => "Patio central - Colegio Demo {$i}",
                'latitude' => 4.10 + ($i / 100),
                'longitude' => -73.60 - ($i / 100),
                'altitude' => 450 + $i,
                'altitude_meters' => 450 + $i,
                'description' => "Estación demo para pruebas de sensores, responsables y relación con registros.",
                'installed_at' => now()->subMonths($i)->toDateString(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->weatherStationIds[$i] = $this->insertFilteredAndGetId('weather_stations', $payload);
        }
    }

    private function seedSensors(): void
    {
        if (! Schema::hasTable('sensors') || empty($this->weatherStationIds)) {
            return;
        }

        for ($i = 1; $i <= 15; $i++) {
            $payload = [
                'school_id' => $this->schoolIds[$i],
                'weather_station_id' => $this->weatherStationIds[$i] ?? null,
                'physical_variable_id' => $this->variableIds[$i] ?? null,
                'name' => "Sensor Demo {$i}",
                'code' => 'SEN-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'sensor_type' => $this->sensorTypeByIndex($i),
                'model' => $this->sensorModelByIndex($i),
                'unit' => DB::table('physical_variables')->where('id', $this->variableIds[$i])->value('unit'),
                'status' => $i % 5 === 0 ? 'mantenimiento' : 'activo',
                'description' => "Sensor demo {$i} asociado a estación meteorológica y variable física.",
                'installed_at' => now()->subDays($i * 3)->toDateString(),
                'last_maintenance_at' => now()->subDays($i)->toDateString(),
                'next_maintenance_at' => now()->addDays($i * 2)->toDateString(),
                'is_active' => $i % 5 !== 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->insertFilteredAndGetId('sensors', $payload);
        }
    }

    private function seedEnvironmentalEvents(): void
    {
        if (! Schema::hasTable('environmental_events')) {
            return;
        }

        $events = [
            'Día Mundial del Agua',
            'Día de la Tierra',
            'Día del Árbol',
            'Semana Ambiental Escolar',
            'Campaña de reciclaje',
            'Jornada de limpieza institucional',
            'Día de la Biodiversidad',
            'Día del Aire Limpio',
            'Foro de cambio climático',
            'Feria de sostenibilidad',
            'Siembra de árboles',
            'Ruta de residuos aprovechables',
            'Taller de economía circular',
            'Monitoreo de lluvia escolar',
            'Socialización EcoData',
        ];

        for ($i = 1; $i <= 15; $i++) {
            $title = $events[$i - 1];

            $payload = [
                'school_id' => $this->schoolIds[$i],
                'created_by' => $this->userBySchool[$this->schoolIds[$i]] ?? $this->userIds[1],
                'title' => $title,
                'slug' => Str::slug($title . '-' . $i),
                'description' => "Evento ambiental demo para probar calendario, modal y visualización pública interna.",
                'event_date' => now()->addDays($i)->toDateString(),
                'starts_at' => now()->addDays($i)->setTime(8, 0),
                'ends_at' => now()->addDays($i)->setTime(10, 0),
                'start_date' => now()->addDays($i)->toDateString(),
                'end_date' => now()->addDays($i + 1)->toDateString(),
                'image_path' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $this->insertFilteredAndGetId('environmental_events', $payload);
        }
    }

    private function seedFieldDiaryActivities(): void
    {
        if (! Schema::hasTable('field_diary_activities')) {
            return;
        }

        for ($i = 1; $i <= 15; $i++) {
            $schoolId = $this->schoolIds[$i];

            $activityPayload = [
                'school_id' => $schoolId,
                'grade_id' => $this->gradeIds[$i],
                'course_id' => $this->courseIds[$i],
                'weather_station_id' => $this->weatherStationIds[$i] ?? null,
                'created_by' => $this->userBySchool[$schoolId] ?? $this->userIds[1],
                'title' => "Diario de Campo Ambiental {$i}",
                'description' => "Actividad demo para observar, registrar e interpretar variables ambientales del entorno escolar.",
                'entry_type' => ['observacion', 'reto', 'portafolio'][($i - 1) % 3],
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(10 + $i),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $activityId = $this->insertFilteredAndGetId('field_diary_activities', $activityPayload);
            $this->fieldDiaryActivityIds[$i] = $activityId;

            if (Schema::hasTable('field_diary_questions')) {
                $questionPayload = [
                    'field_diary_activity_id' => $activityId,
                    'question_text' => "¿Qué cambios ambientales observaste durante la medición {$i}?",
                    'question_type' => $i % 3 === 0 ? 'number' : ($i % 2 === 0 ? 'textarea' : 'text'),
                    'options' => json_encode(['Bajo', 'Medio', 'Alto']),
                    'order' => 1,
                    'is_required' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $this->fieldDiaryQuestionIds[$i] = $this->insertFilteredAndGetId('field_diary_questions', $questionPayload);
            }
        }
    }

    private function seedFieldDiarySubmissions(): void
    {
        if (! Schema::hasTable('field_diary_submissions') || empty($this->fieldDiaryActivityIds)) {
            return;
        }

        $statuses = ['borrador', 'enviado', 'revisado', 'devuelto'];

        for ($i = 1; $i <= 15; $i++) {
            $schoolId = $this->schoolIds[$i];
            $studentId = $this->findStudentForSchool($schoolId) ?? $this->userBySchool[$schoolId] ?? $this->userIds[1];
            $status = $statuses[($i - 1) % count($statuses)];

            $submissionPayload = [
                'field_diary_activity_id' => $this->fieldDiaryActivityIds[$i],
                'user_id' => $studentId,
                'school_id' => $schoolId,
                'grade_id' => $this->gradeIds[$i],
                'course_id' => $this->courseIds[$i],
                'status' => $status,
                'score' => in_array($status, ['revisado', 'devuelto'], true) ? rand(60, 100) : null,
                'teacher_feedback' => in_array($status, ['revisado', 'devuelto'], true)
                    ? "Retroalimentación demo para la entrega {$i}."
                    : null,
                'reviewed_by' => in_array($status, ['revisado', 'devuelto'], true)
                    ? ($this->userBySchool[$schoolId] ?? $this->userIds[1])
                    : null,
                'reviewed_at' => in_array($status, ['revisado', 'devuelto'], true)
                    ? now()->subDays(1)
                    : null,
                'submitted_at' => $status !== 'borrador' ? now()->subDays(2) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $submissionId = $this->insertFilteredAndGetId('field_diary_submissions', $submissionPayload);
            $this->fieldDiarySubmissionIds[$i] = $submissionId;

            if (Schema::hasTable('field_diary_answers') && isset($this->fieldDiaryQuestionIds[$i])) {
                $answerPayload = [
                    'field_diary_submission_id' => $submissionId,
                    'field_diary_question_id' => $this->fieldDiaryQuestionIds[$i],
                    'answer' => "Respuesta demo del estudiante para el diario {$i}.",
                    'answer_text' => "Respuesta demo del estudiante para el diario {$i}.",
                    'value_text' => "Respuesta demo del estudiante para el diario {$i}.",
                    'value_numeric' => $i * 2,
                    'file_path' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $this->insertFilteredAndGetId('field_diary_answers', $answerPayload);
            }
        }
    }

    private function findStudentForSchool(int $schoolId): ?int
    {
        if (! Schema::hasTable('model_has_roles') || ! isset($this->roleIds['estudiante'])) {
            return null;
        }

        return DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
            })
            ->where('users.school_id', $schoolId)
            ->where('model_has_roles.role_id', $this->roleIds['estudiante'])
            ->value('users.id');
    }

    private function insertFilteredAndGetId(string $table, array $payload): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $columns = Schema::getColumnListing($table);

        $filtered = collect($payload)
            ->only($columns)
            ->toArray();

        if (in_array('created_at', $columns, true) && ! array_key_exists('created_at', $filtered)) {
            $filtered['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true) && ! array_key_exists('updated_at', $filtered)) {
            $filtered['updated_at'] = now();
        }

        return DB::table($table)->insertGetId($filtered);
    }

    private function createDemoPdf(): void
    {
        Storage::disk('public')->put(
            'laboratory-guides/demo-guia-ambiental.pdf',
            "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF"
        );
    }

    private function sensorTypeByIndex(int $index): string
    {
        return [
            'BME280',
            'BME280',
            'BMP280',
            'Pluviómetro',
            'Anemómetro',
            'Veleta',
            'ML8511',
            'CCS811',
            'CCS811',
            'Capacitivo',
            'pH',
            'Sonómetro',
            'BH1750',
            'Estado digital',
            'Observación manual',
        ][$index - 1] ?? 'Sensor ambiental';
    }

    private function sensorModelByIndex(int $index): string
    {
        return [
            'Bosch BME280',
            'Bosch BME280',
            'Bosch BMP280',
            'Balancín pluviométrico',
            'Anemómetro de copas',
            'Veleta analógica',
            'ML8511 UV',
            'CCS811 Air Quality',
            'CCS811 VOC',
            'Sensor humedad suelo',
            'Sensor pH analógico',
            'Módulo ruido ambiental',
            'BH1750',
            'Entrada digital',
            'Registro manual',
        ][$index - 1] ?? 'Modelo demo';
    }
}