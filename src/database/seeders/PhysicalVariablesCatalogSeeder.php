<?php

namespace Database\Seeders;

use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableCategory;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PhysicalVariablesCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Clima',
                'slug' => 'clima',
                'description' => 'Variables asociadas a condiciones atmosféricas y meteorológicas.',
                'is_active' => true,
            ],
            [
                'name' => 'Agua',
                'slug' => 'agua',
                'description' => 'Variables relacionadas con calidad y comportamiento del agua.',
                'is_active' => true,
            ],
            [
                'name' => 'Suelo',
                'slug' => 'suelo',
                'description' => 'Variables relacionadas con características físicas y químicas del suelo.',
                'is_active' => true,
            ],
            [
                'name' => 'Laboratorio',
                'slug' => 'laboratorio',
                'description' => 'Variables generales de prácticas y observaciones experimentales.',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $categoryData) {
            PhysicalVariableCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $categoryMap = PhysicalVariableCategory::query()
            ->get()
            ->keyBy('slug');

        $variables = [
            [
                'category_slug' => 'clima',
                'name' => 'Temperatura',
                'slug' => 'temperatura',
                'unit' => '°C',
                'data_type' => 'decimal',
                'min_value' => -20,
                'max_value' => 60,
                'decimals' => 1,
                'description' => 'Temperatura ambiente registrada en grados Celsius.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'clima',
                'name' => 'Humedad relativa',
                'slug' => 'humedad-relativa',
                'unit' => '%',
                'data_type' => 'decimal',
                'min_value' => 0,
                'max_value' => 100,
                'decimals' => 1,
                'description' => 'Porcentaje de humedad relativa del aire.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'clima',
                'name' => 'Precipitación',
                'slug' => 'precipitacion',
                'unit' => 'mm',
                'data_type' => 'decimal',
                'min_value' => 0,
                'max_value' => 1000,
                'decimals' => 2,
                'description' => 'Cantidad de lluvia acumulada en milímetros.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'clima',
                'name' => 'Velocidad del viento',
                'slug' => 'velocidad-viento',
                'unit' => 'm/s',
                'data_type' => 'decimal',
                'min_value' => 0,
                'max_value' => 100,
                'decimals' => 2,
                'description' => 'Velocidad del viento registrada en metros por segundo.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'agua',
                'name' => 'pH del agua',
                'slug' => 'ph-agua',
                'unit' => 'pH',
                'data_type' => 'decimal',
                'min_value' => 0,
                'max_value' => 14,
                'decimals' => 2,
                'description' => 'Nivel de acidez o alcalinidad del agua.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'agua',
                'name' => 'Turbidez',
                'slug' => 'turbidez',
                'unit' => 'NTU',
                'data_type' => 'decimal',
                'min_value' => 0,
                'max_value' => 10000,
                'decimals' => 2,
                'description' => 'Nivel de turbidez del agua.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'suelo',
                'name' => 'Humedad del suelo',
                'slug' => 'humedad-suelo',
                'unit' => '%',
                'data_type' => 'decimal',
                'min_value' => 0,
                'max_value' => 100,
                'decimals' => 1,
                'description' => 'Porcentaje de humedad presente en el suelo.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'suelo',
                'name' => 'pH del suelo',
                'slug' => 'ph-suelo',
                'unit' => 'pH',
                'data_type' => 'decimal',
                'min_value' => 0,
                'max_value' => 14,
                'decimals' => 2,
                'description' => 'Nivel de acidez o alcalinidad del suelo.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'laboratorio',
                'name' => 'Observación cualitativa',
                'slug' => 'observacion-cualitativa',
                'unit' => null,
                'data_type' => 'text',
                'min_value' => null,
                'max_value' => null,
                'decimals' => 0,
                'description' => 'Campo abierto para registrar observaciones descriptivas.',
                'is_active' => true,
            ],
            [
                'category_slug' => 'laboratorio',
                'name' => 'Muestra válida',
                'slug' => 'muestra-valida',
                'unit' => null,
                'data_type' => 'boolean',
                'min_value' => null,
                'max_value' => null,
                'decimals' => 0,
                'description' => 'Indica si la muestra o medición fue considerada válida.',
                'is_active' => true,
            ],
        ];

        $schools = School::query()
            ->where('is_active', true)
            ->get();

        foreach ($schools as $school) {
            foreach ($variables as $variableData) {
                $category = $categoryMap->get($variableData['category_slug']);

                if (! $category) {
                    continue;
                }

                PhysicalVariable::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'slug' => $variableData['slug'],
                    ],
                    [
                        'category_id' => $category->id,
                        'name' => $variableData['name'],
                        'unit' => $variableData['unit'],
                        'data_type' => $variableData['data_type'],
                        'min_value' => $variableData['min_value'],
                        'max_value' => $variableData['max_value'],
                        'decimals' => $variableData['decimals'],
                        'description' => $variableData['description'],
                        'is_active' => $variableData['is_active'],
                    ]
                );
            }
        }
    }
}