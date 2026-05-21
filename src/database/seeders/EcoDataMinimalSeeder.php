<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EcoDataMinimalSeeder extends Seeder
{
    private ?int $schoolId = null;

    public function run(): void
    {
        $this->seedRoles();
        $this->seedSchool();
        $this->seedPhysicalVariableCategories();
        $this->seedPhysicalVariables();
        $this->seedWeatherStation();
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
            DB::table('roles')->updateOrInsert(
                [
                    'name' => $role,
                    'guard_name' => 'web',
                ],
                [
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

        $name = 'Institución Educativa EcoData Demo';
        $slug = Str::slug($name);

        $payload = [
            'name' => $name,
            'slug' => $slug,
            'shield_path' => null,
            'primary_color' => '#22c55e',
            'secondary_color' => '#0f172a',
            'accent_color' => '#86efac',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $this->upsertFiltered('schools', ['slug' => $slug], $payload);

        $this->schoolId = DB::table('schools')
            ->where('slug', $slug)
            ->value('id');
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
                ['slug' => $slug],
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
            ['Humedad relativa', 'humedad-relativa', 'Humedad', '%', 'decimal', 0, 100, 2],
            ['Presión atmosférica', 'presion-atmosferica', 'Presión atmosférica', 'hPa', 'decimal', 800, 1100, 2],
            ['Precipitación acumulada', 'precipitacion-acumulada', 'Precipitación', 'mm', 'decimal', 0, 300, 2],
            ['Velocidad del viento', 'velocidad-viento', 'Viento', 'm/s', 'decimal', 0, 80, 2],
            ['Dirección del viento', 'direccion-viento', 'Viento', '°', 'integer', 0, 360, 0],
            ['Radiación solar', 'radiacion-solar', 'Radiación solar', 'W/m²', 'decimal', 0, 1400, 2],
            ['Índice UV', 'indice-uv', 'Radiación ultravioleta', 'UV', 'decimal', 0, 15, 2],
            ['CO2 equivalente', 'co2-equivalente', 'Gases ambientales', 'ppm', 'integer', 300, 5000, 0],
            ['Compuestos orgánicos volátiles', 'cov', 'Calidad del aire', 'ppb', 'integer', 0, 2000, 0],
            ['Humedad del suelo', 'humedad-suelo', 'Suelo', '%', 'decimal', 0, 100, 2],
            ['pH del agua', 'ph-agua', 'Agua', 'pH', 'decimal', 0, 14, 2],
            ['Nivel de ruido', 'nivel-ruido', 'Ruido ambiental', 'dB', 'decimal', 20, 130, 2],
            ['Luminosidad', 'luminosidad', 'Luminosidad', 'lux', 'integer', 0, 100000, 0],
            ['Observación ambiental', 'observacion-ambiental', 'Observaciones cualitativas', null, 'text', null, null, 0],
        ];

        foreach ($variables as $variable) {
            [$name, $slug, $categoryName, $unit, $dataType, $minValue, $maxValue, $decimals] = $variable;

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

        $payload = [
            'school_id' => $this->schoolId,
            'responsible_user_id' => null,
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

        DB::table($table)->updateOrInsert($filteredWhere, $filteredPayload);
    }
}