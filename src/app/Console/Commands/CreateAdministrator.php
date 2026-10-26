<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateAdministrator extends Command
{
    protected $signature = 'ecodata:admin';

    protected $description = 'Crea una cuenta inicial super_admin sin credenciales predefinidas.';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Nombre'),
            'email' => strtolower(trim((string) $this->ask('Correo controlado por el administrador'))),
            'password' => $this->secret('Contraseña única (mínimo 12 caracteres)'),
            'password_confirmation' => $this->secret('Repetir contraseña'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        DB::transaction(function () use ($data) {
            Role::findOrCreate('super_admin', 'web');
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => Hash::make($data['password']), 'is_active' => true,
            ]);
            $user->assignRole('super_admin');
        });
        $this->info('Administrador creado. Al ingresar deberá verificar el correo configurado. No se modificaron otras cuentas.');

        return self::SUCCESS;
    }
}
