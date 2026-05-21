<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EcoDataInitialSeeder extends Seeder
{
    private ?int $schoolId = null;
    private ?int $gradeId = null;
    private ?int $courseId = null;

    public function run(): void
    {
        DB::transaction(function () {
            $this->seedRoles();
            $this->seedSchool();
            $this->seedGrade();
            $this->seedCourse();
            $this->seedUsers();
            $this->seedPhysicalVariableCategories();
            $this->seedPhysicalVariables();
            $this->seedWeatherStation();
        });
    }

    private function seedRoles(): void
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
            $this->upsertFiltered(
                'roles',
                [
                    'name' => $role,
                    'guard_name' => 'web',
                ],
                [
                    'name' => $role,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedSchool(): void
    {
        if (! Schema::hasTable('schools')) {
            return;
        }

        $name = 'Institución Educativa EcoData';
        $slug = Str::slug($name);

        $this->upsertFiltered(
            'schools',
            [
                'slug' => $slug,
            ],
            [
                'name' => $name,
                'slug' => $slug,
                'shield_path' => null,
                'primary_color' => '#22c55e',
                'secondary_color' => '#0f172a',
                'accent_color' => '#86efac',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->schoolId = DB::table('schools')
            ->where('slug', $slug)
            ->value('id');
    }

    private function seedGrade(): void
    {
        if (! Schema::hasTable('grades') || ! $this->schoolId) {
            return;
        }

        $this->upsertFiltered(
            'grades',
            [
                'school_id' => $this->schoolId,
                'name' => '6',
            ],
            [
                'school_id' => $this->schoolId,
                'name' => '6',
                'label' => 'Grado Sexto',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->gradeId = DB::table('grades')
            ->where('school_id', $this->schoolId)
            ->where('name', '6')
            ->value('id');
    }

    private function seedCourse(): void
    {
        if (! Schema::hasTable('courses') || ! $this->schoolId || ! $this->gradeId) {
            return;
        }

        $this->upsertFiltered(
            'courses',
            [
                'school_id' => $this->schoolId,
                'grade_id' => $this->gradeId,
                'name' => '601',
            ],
            [
                'school_id' => $this->schoolId,
                'grade_id' => $this->gradeId,
                'name' => '601',
                'label' => '601',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->courseId = DB::table('courses')
            ->where('school_id', $this->schoolId)
            ->where('grade_id', $this->gradeId)
            ->where('name', '601')
            ->value('id');
    }

    private function seedUsers(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $users = [
            [
                'name' => 'Super Admin EcoData',
                'email' => 'superadmin@ecodata.com',
                'role' => 'super_admin',
                'school_id' => null,
                'grade_id' => null,
                'course_id' => null,
                'document_type' => 'CC',
                'document_number' => '1000000001',
            ],
            [
                'name' => 'Admin Colegio EcoData',
                'email' => 'admin@ecodata.com',
                'role' => 'admin_colegio',
                'school_id' => $this->schoolId,
                'grade_id' => null,
                'course_id' => null,
                'document_type' => 'CC',
                'document_number' => '1000000002',
            ],
            [
                'name' => 'Docente EcoData',
                'email' => 'docente@ecodata.com',
                'role' => 'docente',
                'school_id' => $this->schoolId,
                'grade_id' => null,
                'course_id' => null,
                'document_type' => 'CC',
                'document_number' => '1000000003',
            ],
            [
                'name' => 'Estudiante EcoData',
                'email' => 'estudiante@ecodata.com',
                'role' => 'estudiante',
                'school_id' => $this->schoolId,
                'grade_id' => $this->gradeId,
                'course_id' => $this->courseId,
                'document_type' => 'TI',
                'document_number' => '1000000004',
            ],
        ];

        foreach ($users as $user) {
            $this->upsertFiltered(
                'users',
                [
                    'email' => $user['email'],
                ],
                [
                    'school_id' => $user['school_id'],
                    'grade_id' => $user['grade_id'],
                    'course_id' => $user['course_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'document_type' => $user['document_type'],
                    'document_number' => $user['document_number'],
                    'email_verified_at' => now(),
                    'password' => Hash::make('Cambio123*'),
                    'remember_token' => Str::random(10),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $userId = DB::table('users')
                ->where('email', $user['email'])
                ->value('id');

            $roleId = DB::table('roles')
                ->where('name', $user['role'])
                ->where('guard_name', 'web')
                ->value('id');

            if ($userId && $roleId && Schema::hasTable('model_has_roles')) {
                $this->upsertFiltered(
                    'model_has_roles',
                    [
                        'role_id' => $roleId,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $userId,
                    ],
                    [
                        'role_id' => $roleId,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $userId,
                    ]
                );
            }
        }
    }

    private function seedPhysicalVariableCategories(): void
    {
        if (! Schema::hasTable('physical_variable_categories')) {
            return;
        }

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

        foreach ($categories as $name) {
            $slug = Str::slug($name);

            $this->upsertFiltered(
                'physical_variable_categories',
                [
                    'slug' => $slug,
                ],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => "Categoría para variables relacionadas con {$name}.",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedPhysicalVariables(): void
    {
        if (! Schema::hasTable('physical_variables') || ! $this->schoolId) {
            return;
        }

        $variables = [
            ['Temperatura ambiente', 'temperatura-ambiente', 'Temperatura', '°C', 'decimal', -10, 60, 2],
            ['Temperatura mínima', 'temperatura-minima', 'Temperatura', '°C', 'decimal', -10, 60, 2],
            ['Temperatura máxima', 'temperatura-maxima', 'Temperatura', '°C', 'decimal', -10, 60, 2],

            ['Humedad relativa', 'humedad-relativa', 'Humedad', '%', 'decimal', 0, 100, 2],
            ['Humedad absoluta', 'humedad-absoluta', 'Humedad', 'g/m³', 'decimal', 0, 100, 2],

            ['Presión atmosférica', 'presion-atmosferica', 'Presión atmosférica', 'hPa', 'decimal', 800, 1100, 2],

            ['Precipitación acumulada', 'precipitacion-acumulada', 'Precipitación', 'mm', 'decimal', 0, 300, 2],
            ['Intensidad de lluvia', 'intensidad-lluvia', 'Precipitación', 'mm/h', 'decimal', 0, 200, 2],

            ['Velocidad del viento', 'velocidad-viento', 'Viento', 'm/s', 'decimal', 0, 80, 2],
            ['Dirección del viento', 'direccion-viento', 'Viento', '°', 'integer', 0, 360, 0],
            ['Ráfaga de viento', 'rafaga-viento', 'Viento', 'm/s', 'decimal', 0, 100, 2],

            ['Radiación solar', 'radiacion-solar', 'Radiación solar', 'W/m²', 'decimal', 0, 1400, 2],
            ['Irradiancia solar', 'irradiancia-solar', 'Radiación solar', 'W/m²', 'decimal', 0, 1400, 2],

            ['Índice UV', 'indice-uv', 'Radiación ultravioleta', 'UV', 'decimal', 0, 15, 2],

            ['Material particulado PM2.5', 'pm25', 'Calidad del aire', 'µg/m³', 'decimal', 0, 500, 2],
            ['Material particulado PM10', 'pm10', 'Calidad del aire', 'µg/m³', 'decimal', 0, 600, 2],
            ['Índice de calidad del aire', 'ica', 'Calidad del aire', 'ICA', 'integer', 0, 500, 0],

            ['CO2 equivalente', 'co2-equivalente', 'Gases ambientales', 'ppm', 'integer', 300, 5000, 0],
            ['Compuestos orgánicos volátiles', 'cov', 'Gases ambientales', 'ppb', 'integer', 0, 2000, 0],

            ['Humedad del suelo', 'humedad-suelo', 'Suelo', '%', 'decimal', 0, 100, 2],
            ['Temperatura del suelo', 'temperatura-suelo', 'Suelo', '°C', 'decimal', -10, 60, 2],

            ['pH del agua', 'ph-agua', 'Agua', 'pH', 'decimal', 0, 14, 2],
            ['Temperatura del agua', 'temperatura-agua', 'Agua', '°C', 'decimal', 0, 60, 2],
            ['Turbidez del agua', 'turbidez-agua', 'Agua', 'NTU', 'decimal', 0, 1000, 2],

            ['Nivel de ruido', 'nivel-ruido', 'Ruido ambiental', 'dB', 'decimal', 20, 130, 2],

            ['Luminosidad', 'luminosidad', 'Luminosidad', 'lux', 'integer', 0, 100000, 0],

            ['Voltaje del sistema', 'voltaje-sistema', 'Energía', 'V', 'decimal', 0, 30, 2],
            ['Nivel de batería', 'nivel-bateria', 'Energía', '%', 'decimal', 0, 100, 2],

            ['Observación ambiental', 'observacion-ambiental', 'Observaciones cualitativas', null, 'text', null, null, 0],
            ['Estado del sensor', 'estado-sensor', 'Observaciones cualitativas', null, 'boolean', null, null, 0],
        ];

        foreach ($variables as $variable) {
            [
                $name,
                $slug,
                $categoryName,
                $unit,
                $dataType,
                $minValue,
                $maxValue,
                $decimals,
            ] = $variable;

            $categoryId = DB::table('physical_variable_categories')
                ->where('slug', Str::slug($categoryName))
                ->value('id');

            $this->upsertFiltered(
                'physical_variables',
                [
                    'school_id' => $this->schoolId,
                    'slug' => $slug,
                ],
                [
                    'school_id' => $this->schoolId,
                    'category_id' => $categoryId,
                    'name' => $name,
                    'slug' => $slug,
                    'unit' => $unit,
                    'data_type' => $dataType,
                    'min_value' => $minValue,
                    'max_value' => $maxValue,
                    'decimals' => $decimals,
                    'description' => "Variable física {$name} para registros ambientales escolares.",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedWeatherStation(): void
    {
        if (! Schema::hasTable('weather_stations') || ! $this->schoolId) {
            return;
        }

        $adminUserId = DB::table('users')
            ->where('email', 'admin@ecodata.com')
            ->value('id');

        $payload = [
            'school_id' => $this->schoolId,
            'responsible_user_id' => $adminUserId,
            'name' => 'Estación Meteorológica Escolar EcoData',
            'code' => 'EME-001',
            'location' => 'Patio central institucional',
            'latitude' => 4.142,
            'longitude' => -73.626,
            'altitude' => 467,
            'altitude_meters' => 467,
            'description' => 'Estación meteorológica escolar para el registro de variables ambientales.',
            'installed_at' => now()->toDateString(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->upsertFiltered(
            'weather_stations',
            [
                'school_id' => $this->schoolId,
                'code' => 'EME-001',
            ],
            $payload
        );
    }

    private function upsertFiltered(string $table, array $where, array $payload): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columns = Schema::getColumnListing($table);

        $filteredWhere = collect($where)
            ->only($columns)
            ->toArray();

        $filteredPayload = collect($payload)
            ->only($columns)
            ->toArray();

        if (empty($filteredWhere)) {
            return;
        }

        if (in_array('created_at', $columns, true) && ! array_key_exists('created_at', $filteredPayload)) {
            $filteredPayload['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true) && ! array_key_exists('updated_at', $filteredPayload)) {
            $filteredPayload['updated_at'] = now();
        }

        DB::table($table)->updateOrInsert($filteredWhere, $filteredPayload);
    }
}