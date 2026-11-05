<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class SchoolBootstrapService
{
    private const ROLES = ['super_admin', 'admin_colegio', 'docente', 'estudiante'];

    public function inspect(array $configuration): array
    {
        $data = $this->validateConfiguration($configuration);
        $roles = $this->roles();
        $schools = School::query()->get();
        $users = User::query()->with('roles')->get();
        $state = $this->state($data, $schools, $users);

        return [
            'mode' => 'preview',
            'state' => $state,
            'roles_ready' => $roles->count() === 4,
            'existing_schools' => $schools->count(),
            'existing_users' => $users->count(),
            'schools_to_create' => $state === 'new' ? 1 : 0,
            'users_to_create' => $state === 'new' ? 4 : 0,
        ];
    }

    public function prepare(array $configuration, ?string $password, bool $verifiedOperatorEmails): array
    {
        $data = $this->validateConfiguration($configuration);
        if (! $verifiedOperatorEmails) {
            $this->invalid('El operador debe confirmar el control de los cuatro correos con --verified-operator-emails.');
        }

        return DB::transaction(function () use ($data, $password): array {
            // Existing role rows serialize simultaneous bootstrap attempts on an empty installation.
            $roles = $this->roles(lock: true)->keyBy('name');
            if ($roles->count() !== 4) {
                $this->invalid('Deben existir los cuatro roles autorizados antes del bootstrap; este comando no ejecuta seeders.');
            }
            $schools = School::query()->orderBy('id')->lockForUpdate()->get();
            $users = User::query()->with('roles')->orderBy('id')->lockForUpdate()->get();
            $state = $this->state($data, $schools, $users);
            if ($state === 'blocked') {
                $this->invalid('La base contiene colegios o cuentas distintos del bootstrap solicitado. No se sobrescribirán ni se reasignarán datos.');
            }
            if ($state === 'preserved') {
                return ['mode' => 'applied', 'state' => 'preserved', 'schools_created' => 0, 'users_created' => 0];
            }

            Validator::make(['password' => $password], [
                'password' => ['required', 'string', 'min:8', 'max:128'],
            ])->validate();

            $school = School::create([
                'name' => $data['school']['name'],
                'slug' => $data['school']['slug'],
                'is_active' => true,
            ]);
            foreach ($data['users'] as $account) {
                $user = new User;
                $user->forceFill([
                    'name' => $account['name'],
                    'email' => $account['email'],
                    'password' => Hash::make($password),
                    'is_active' => true,
                    // Explicit operator-controlled bootstrap; normal account verification is unchanged.
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(60),
                    'school_id' => $account['role'] === 'super_admin' ? null : $school->id,
                    'grade_id' => null,
                    'course_id' => null,
                    'document_type' => null,
                    'document_number' => null,
                ])->save();
                $user->assignRole($roles[$account['role']]);
            }

            return [
                'mode' => 'applied',
                'state' => 'created',
                'school_id' => $school->id,
                'schools_created' => 1,
                'users_created' => 4,
            ];
        });
    }

    private function validateConfiguration(array $configuration): array
    {
        if (! app()->runningInConsole() || ! app()->environment('production')) {
            $this->invalid('El bootstrap solo está disponible en consola con APP_ENV=production.');
        }

        foreach (is_array($configuration['users'] ?? null) ? $configuration['users'] : [] as $index => $account) {
            if (is_array($account) && is_string($account['email'] ?? null)) {
                $configuration['users'][$index]['email'] = Str::lower(trim($account['email']));
            }
        }
        Validator::make($configuration, [
            'school' => ['required', 'array:name'],
            'school.name' => ['required', 'string', 'max:255'],
            'users' => ['required', 'array', 'size:4'],
            'users.*' => ['required', 'array:name,email,role'],
            'users.*.name' => ['required', 'string', 'max:255'],
            'users.*.email' => ['required', 'string', 'email:rfc', 'max:255', 'distinct:ignore_case'],
            'users.*.role' => ['required', 'string', Rule::in(self::ROLES), 'distinct'],
        ])->validate();
        if (array_diff(array_keys($configuration), ['school', 'users']) !== []) {
            $this->invalid('El archivo solo admite school y users; la contraseña va en un archivo separado.');
        }
        $configuration['school']['name'] = trim($configuration['school']['name']);
        $configuration['school']['slug'] = Str::slug($configuration['school']['name']);
        if ($configuration['school']['name'] === '' || $configuration['school']['slug'] === '') {
            $this->invalid('El nombre del colegio debe permitir generar un identificador válido.');
        }
        foreach ($configuration['users'] as &$account) {
            $account['name'] = trim($account['name']);
            if ($account['name'] === '') {
                $this->invalid('Cada cuenta debe tener un nombre.');
            }
        }

        return $configuration;
    }

    private function roles(bool $lock = false): Collection
    {
        $query = Role::query()->where('guard_name', 'web')->whereIn('name', self::ROLES)->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    private function state(array $data, Collection $schools, Collection $users): string
    {
        if ($schools->isEmpty() && $users->isEmpty()) {
            return 'new';
        }
        if ($schools->count() !== 1 || $users->count() !== 4) {
            return 'blocked';
        }
        $school = $schools->first();
        if ($school->name !== $data['school']['name'] || $school->slug !== $data['school']['slug'] || ! $school->is_active) {
            return 'blocked';
        }
        foreach ($data['users'] as $account) {
            $user = $users->first(fn (User $candidate): bool => Str::lower($candidate->email) === $account['email']);
            $schoolId = $account['role'] === 'super_admin' ? null : (int) $school->id;
            if ($user === null || $user->name !== $account['name'] || ! $user->is_active || ! $user->hasVerifiedEmail()
                || ($user->school_id === null ? null : (int) $user->school_id) !== $schoolId
                || $user->grade_id !== null || $user->course_id !== null
                || $user->document_type !== null || $user->document_number !== null
                || $user->roles->count() !== 1 || ! $user->hasRole($account['role'], 'web')) {
                return 'blocked';
            }
        }

        return 'preserved';
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['bootstrap' => $message]);
    }
}
