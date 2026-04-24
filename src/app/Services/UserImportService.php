<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Grade;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserImportService
{
    public function process(array $rows, string $mode = 'create_only'): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $line = $index + 2;

            try {
                $normalized = $this->normalizeRow($row);

                if (! $normalized['name'] || ! $normalized['email'] || ! $normalized['document_type'] || ! $normalized['document_number'] || ! $normalized['role']) {
                    $summary['errors'][] = "Fila {$line}: faltan campos obligatorios.";
                    continue;
                }

                $school = $this->findSchool($normalized['school']);
                $grade = $this->findGrade($school?->id, $normalized['grade']);
                $course = $this->findCourse($school?->id, $grade?->id, $normalized['course']);
                $role = Role::where('name', $normalized['role'])->first();

                if (! $role) {
                    $summary['errors'][] = "Fila {$line}: el rol '{$normalized['role']}' no existe.";
                    continue;
                }

                $existingUser = User::query()
                    ->where('document_number', $normalized['document_number'])
                    ->orWhere('email', $normalized['email'])
                    ->first();

                if ($mode === 'create_only' && $existingUser) {
                    $summary['skipped']++;
                    continue;
                }

                $password = $normalized['password']
                    ? Hash::make($normalized['password'])
                    : Hash::make('Cambio123*');

                $user = User::updateOrCreate(
                    [
                        'document_number' => $normalized['document_number'],
                    ],
                    [
                        'name' => $normalized['name'],
                        'email' => $normalized['email'],
                        'document_type' => $normalized['document_type'],
                        'password' => $existingUser ? $existingUser->password : $password,
                        'school_id' => $school?->id,
                        'grade_id' => $grade?->id,
                        'course_id' => $course?->id,
                        'is_active' => $normalized['is_active'],
                        'email_verified_at' => $existingUser?->email_verified_at ?? now(),
                    ]
                );

                $user->syncRoles([$role->name]);

                if ($existingUser) {
                    $summary['updated']++;
                } else {
                    $summary['created']++;
                }
            } catch (\Throwable $e) {
                $summary['errors'][] = "Fila {$line}: {$e->getMessage()}";
            }
        }

        return $summary;
    }

    protected function normalizeRow(array $row): array
    {
        return [
            'name' => trim((string) ($row['name'] ?? $row['nombre'] ?? '')),
            'email' => trim((string) ($row['email'] ?? $row['correo'] ?? '')),
            'document_type' => strtoupper(trim((string) ($row['document_type'] ?? $row['tipo_identificacion'] ?? ''))),
            'document_number' => trim((string) ($row['document_number'] ?? $row['numero_identificacion'] ?? '')),
            'role' => trim((string) ($row['role'] ?? $row['rol'] ?? '')),
            'school' => trim((string) ($row['school'] ?? $row['colegio'] ?? '')),
            'grade' => trim((string) ($row['grade'] ?? $row['grado'] ?? '')),
            'course' => trim((string) ($row['course'] ?? $row['curso'] ?? '')),
            'password' => trim((string) ($row['password'] ?? $row['contrasena'] ?? '')),
            'is_active' => $this->toBoolean($row['is_active'] ?? $row['activo'] ?? true),
        ];
    }

    protected function findSchool(?string $value): ?School
    {
        if (! $value) {
            return null;
        }

        return School::query()
            ->where('name', $value)
            ->orWhere('slug', Str::slug($value))
            ->first();
    }

    protected function findGrade(?int $schoolId, ?string $value): ?Grade
    {
        if (! $schoolId || ! $value) {
            return null;
        }

        return Grade::query()
            ->where('school_id', $schoolId)
            ->where(function ($query) use ($value) {
                $query->where('name', $value)->orWhere('label', $value);
            })
            ->first();
    }

    protected function findCourse(?int $schoolId, ?int $gradeId, ?string $value): ?Course
    {
        if (! $schoolId || ! $gradeId || ! $value) {
            return null;
        }

        return Course::query()
            ->where('school_id', $schoolId)
            ->where('grade_id', $gradeId)
            ->where(function ($query) use ($value) {
                $query->where('name', $value)->orWhere('label', $value);
            })
            ->first();
    }

    protected function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'si', 'sí', 'activo', 'yes'], true);
    }
}