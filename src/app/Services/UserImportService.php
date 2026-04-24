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
    public function process(array $rows, string $mode = 'create_only', ?User $authUser = null): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (! $authUser) {
            $summary['errors'][] = 'No fue posible identificar el usuario autenticado para aplicar las reglas de importación.';
            return $summary;
        }

        if (! $authUser->hasAnyRole(['super_admin', 'admin_colegio'])) {
            $summary['errors'][] = 'No tienes autorización para importar usuarios.';
            return $summary;
        }

        if (! $authUser->hasRole('super_admin') && ! $authUser->school_id) {
            $summary['errors'][] = 'Tu usuario no tiene un colegio asignado. No es posible importar usuarios.';
            return $summary;
        }

        foreach ($rows as $index => $row) {
            $line = $index + 1;

            try {
                $normalized = $this->normalizeRow($row);

                $requiredError = $this->validateRequiredFields($normalized, $authUser);

                if ($requiredError) {
                    $summary['errors'][] = "Fila de datos {$line}: {$requiredError}";
                    continue;
                }

                $role = $this->findAllowedRole($normalized['role'], $authUser);

                if (! $role) {
                    $summary['errors'][] = "Fila de datos {$line}: el rol '{$normalized['role']}' no existe o no está permitido para tu usuario.";
                    continue;
                }

                $school = $this->resolveSchool($normalized['school'], $authUser);

                if (! $school && $role->name !== 'super_admin') {
                    $summary['errors'][] = "Fila de datos {$line}: debes indicar un colegio válido para usuarios que no sean super_admin.";
                    continue;
                }

                $grade = null;

                if ($normalized['grade']) {
                    $grade = $this->findGrade($school?->id, $normalized['grade']);

                    if (! $grade) {
                        $summary['errors'][] = "Fila de datos {$line}: el grado '{$normalized['grade']}' no existe o no pertenece al colegio indicado.";
                        continue;
                    }
                }

                $course = null;

                if ($normalized['course']) {
                    if (! $grade) {
                        $summary['errors'][] = "Fila de datos {$line}: para asignar curso debes indicar primero un grado válido.";
                        continue;
                    }

                    $course = $this->findCourse($school?->id, $grade->id, $normalized['course']);

                    if (! $course) {
                        $summary['errors'][] = "Fila de datos {$line}: el curso '{$normalized['course']}' no existe o no pertenece al colegio y grado indicados.";
                        continue;
                    }
                }

                $existingByDocument = User::query()
                    ->with('roles')
                    ->where('document_number', $normalized['document_number'])
                    ->first();

                $existingByEmail = User::query()
                    ->with('roles')
                    ->where('email', $normalized['email'])
                    ->first();

                if (
                    $existingByDocument
                    && $existingByEmail
                    && (int) $existingByDocument->id !== (int) $existingByEmail->id
                ) {
                    $summary['errors'][] = "Fila de datos {$line}: el documento y el correo pertenecen a usuarios diferentes.";
                    continue;
                }

                $existingUser = $existingByDocument ?: $existingByEmail;

                if ($mode === 'create_only' && $existingUser) {
                    $summary['skipped']++;
                    continue;
                }

                if ($existingUser) {
                    $scopeError = $this->validateExistingUserScope($existingUser, $authUser);

                    if ($scopeError) {
                        $summary['errors'][] = "Fila de datos {$line}: {$scopeError}";
                        continue;
                    }
                }

                $payload = [
                    'name' => $normalized['name'],
                    'email' => $normalized['email'],
                    'document_type' => $normalized['document_type'],
                    'document_number' => $normalized['document_number'],
                    'school_id' => $school?->id,
                    'grade_id' => $grade?->id,
                    'course_id' => $course?->id,
                    'is_active' => $normalized['is_active'],
                ];

                if (! $existingUser) {
                    $payload['password'] = Hash::make($normalized['password'] ?: 'Cambio123*');
                    $payload['email_verified_at'] = now();

                    $user = User::create($payload);
                    $user->syncRoles([$role->name]);

                    $summary['created']++;
                    continue;
                }

                $existingUser->update($payload);
                $existingUser->syncRoles([$role->name]);

                $summary['updated']++;
            } catch (\Throwable $e) {
                $summary['errors'][] = "Fila de datos {$line}: {$e->getMessage()}";
            }
        }

        return $summary;
    }

    protected function normalizeRow(array $row): array
    {
        return [
            'name' => trim((string) ($row['name'] ?? $row['nombre'] ?? '')),
            'email' => strtolower(trim((string) ($row['email'] ?? $row['correo'] ?? ''))),
            'document_type' => strtoupper(trim((string) ($row['document_type'] ?? $row['tipo_identificacion'] ?? ''))),
            'document_number' => trim((string) ($row['document_number'] ?? $row['numero_identificacion'] ?? '')),
            'role' => strtolower(trim((string) ($row['role'] ?? $row['rol'] ?? ''))),
            'school' => trim((string) ($row['school'] ?? $row['colegio'] ?? '')),
            'grade' => trim((string) ($row['grade'] ?? $row['grado'] ?? '')),
            'course' => trim((string) ($row['course'] ?? $row['curso'] ?? '')),
            'password' => trim((string) ($row['password'] ?? $row['contrasena'] ?? '')),
            'is_active' => $this->toBoolean($row['is_active'] ?? $row['activo'] ?? true),
        ];
    }

    protected function validateRequiredFields(array $normalized, User $authUser): ?string
    {
        if (! $normalized['name']) {
            return 'el nombre es obligatorio.';
        }

        if (! $normalized['email']) {
            return 'el correo electrónico es obligatorio.';
        }

        if (! filter_var($normalized['email'], FILTER_VALIDATE_EMAIL)) {
            return "el correo electrónico '{$normalized['email']}' no es válido.";
        }

        if (! $normalized['document_type']) {
            return 'el tipo de identificación es obligatorio.';
        }

        if (! in_array($normalized['document_type'], ['CC', 'TI', 'CE', 'PPT', 'NIT', 'PAS'], true)) {
            return "el tipo de identificación '{$normalized['document_type']}' no está permitido.";
        }

        if (! $normalized['document_number']) {
            return 'el número de identificación es obligatorio.';
        }

        if (! $normalized['role']) {
            return 'el rol es obligatorio.';
        }

        if ($normalized['role'] === 'super_admin' && ! $authUser->hasRole('super_admin')) {
            return 'no tienes autorización para importar usuarios con rol super_admin.';
        }

        if (! $authUser->hasRole('super_admin') && $normalized['school']) {
            $authSchoolName = $authUser->school?->name;
            $authSchoolSlug = $authUser->school?->slug;

            $incomingSchoolSlug = Str::slug($normalized['school']);

            if (
                $incomingSchoolSlug !== Str::slug((string) $authSchoolName)
                && $incomingSchoolSlug !== Str::slug((string) $authSchoolSlug)
            ) {
                return 'no puedes importar usuarios para un colegio diferente al tuyo.';
            }
        }

        return null;
    }

    protected function findAllowedRole(string $roleName, User $authUser): ?Role
    {
        return Role::query()
            ->where('name', $roleName)
            ->when(
                ! $authUser->hasRole('super_admin'),
                fn ($query) => $query->where('name', '!=', 'super_admin')
            )
            ->first();
    }

    protected function resolveSchool(?string $value, User $authUser): ?School
    {
        if (! $authUser->hasRole('super_admin')) {
            return $authUser->school;
        }

        if (! $value) {
            return null;
        }

        return $this->findSchool($value);
    }

    protected function validateExistingUserScope(User $existingUser, User $authUser): ?string
    {
        if ($authUser->hasRole('super_admin')) {
            return null;
        }

        if ((int) $existingUser->school_id !== (int) $authUser->school_id) {
            return 'no puedes actualizar usuarios de otro colegio.';
        }

        if ($existingUser->hasRole('super_admin')) {
            return 'no puedes actualizar usuarios con rol super_admin.';
        }

        return null;
    }

    protected function findSchool(?string $value): ?School
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);
        $slug = Str::slug($value);

        return School::query()
            ->where('name', $value)
            ->orWhere('slug', $value)
            ->orWhere('slug', $slug)
            ->first();
    }

    protected function findGrade(?int $schoolId, ?string $value): ?Grade
    {
        if (! $schoolId || ! $value) {
            return null;
        }

        $value = trim($value);

        return Grade::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->where(function ($query) use ($value) {
                $query->where('name', $value)
                    ->orWhere('label', $value);
            })
            ->first();
    }

    protected function findCourse(?int $schoolId, ?int $gradeId, ?string $value): ?Course
    {
        if (! $schoolId || ! $gradeId || ! $value) {
            return null;
        }

        $value = trim($value);

        return Course::query()
            ->where('school_id', $schoolId)
            ->where('grade_id', $gradeId)
            ->where('is_active', true)
            ->where(function ($query) use ($value) {
                $query->where('name', $value)
                    ->orWhere('label', $value);
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