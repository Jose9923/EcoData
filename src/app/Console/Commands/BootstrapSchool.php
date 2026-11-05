<?php

namespace App\Console\Commands;

use App\Services\SchoolBootstrapService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class BootstrapSchool extends Command
{
    protected $signature = 'ecodata:bootstrap-school
        {--config-file= : JSON privado externo con school.name y cuatro users con name, email y role}
        {--password-file= : Archivo privado externo con la contraseña expresamente elegida por el operador}
        {--verified-operator-emails : El operador confirma el control de los cuatro correos iniciales}
        {--apply : Crea un colegio y cuatro cuentas solamente en una instalación vacía}';

    protected $description = 'Previsualiza o prepara el colegio y cuatro cuentas iniciales sin enviar correos ni modificar cuentas existentes.';

    public function handle(SchoolBootstrapService $bootstrap): int
    {
        $password = null;
        try {
            $configuration = json_decode($this->readPrivateFile((string) $this->option('config-file'), 16384), true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($configuration) || array_is_list($configuration)) {
                throw ValidationException::withMessages(['bootstrap' => 'La configuración debe ser un objeto JSON con school y users.']);
            }
            $preview = $bootstrap->inspect($configuration);
            if (! $this->option('apply')) {
                $this->line(json_encode($preview, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                $this->comment('Vista previa sin cambios. No se leyó la contraseña ni se enviaron correos.');

                return self::SUCCESS;
            }
            if (! $this->option('verified-operator-emails')) {
                throw ValidationException::withMessages(['bootstrap' => 'Para aplicar, confirma el control de los correos con --verified-operator-emails.']);
            }
            if ($preview['state'] === 'new') {
                $password = $this->readPrivateFile((string) $this->option('password-file'), 2048);
                $password = preg_replace('/\r?\n\z/', '', $password);
                if ($password === '' || str_contains($password, "\n") || str_contains($password, "\r") || str_contains($password, "\0")) {
                    throw ValidationException::withMessages(['bootstrap' => 'El archivo debe contener una sola contraseña, sin líneas adicionales ni caracteres nulos.']);
                }
            }
            $result = $bootstrap->prepare($configuration, $password, (bool) $this->option('verified-operator-emails'));
            $this->line(json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $this->comment('No se enviaron correos ni se cambiaron credenciales existentes. Grado, curso y documentos se dejan sin asignar.');

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::INVALID;
        } catch (Throwable) {
            $this->error('No fue posible completar el bootstrap. Revisa los archivos privados, las migraciones, los roles y el entorno production. No se muestran detalles sensibles.');

            return self::FAILURE;
        } finally {
            unset($password);
        }
    }

    private function readPrivateFile(string $path, int $maximumBytes): string
    {
        $real = realpath($path);
        $info = @lstat($path);
        if (! str_starts_with($path, '/') || $real === false || $info === false
            || ($info['mode'] & 0170000) !== 0100000 || ($info['mode'] & 07177) !== 0
            || ($info['mode'] & 0400) === 0) {
            throw ValidationException::withMessages(['bootstrap' => 'Los archivos deben ser regulares, de ruta absoluta y permisos 0400 o 0600, sin enlaces simbólicos.']);
        }
        $repository = is_dir(base_path('../.git')) ? dirname(base_path()) : base_path();
        foreach ([$repository, public_path(), storage_path('app/public')] as $directory) {
            foreach (array_unique(array_filter([$directory, realpath($directory)])) as $forbidden) {
                if ($real === $forbidden || str_starts_with($real, rtrim($forbidden, '/').'/')) {
                    throw ValidationException::withMessages(['bootstrap' => 'Los archivos deben permanecer fuera del repositorio y de cualquier carpeta pública.']);
                }
            }
        }
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['bootstrap' => 'No se pudo leer el archivo privado.']);
        }
        try {
            $opened = fstat($handle);
            if ($opened === false || $opened['ino'] !== $info['ino'] || $opened['dev'] !== $info['dev']
                || ($opened['mode'] & 07177) !== 0 || $opened['size'] > $maximumBytes) {
                throw ValidationException::withMessages(['bootstrap' => 'El archivo privado cambió o excede el tamaño admitido.']);
            }
            $content = stream_get_contents($handle, $maximumBytes + 1);
            if (! is_string($content) || strlen($content) > $maximumBytes) {
                throw ValidationException::withMessages(['bootstrap' => 'No se pudo leer el contenido del archivo privado.']);
            }

            return $content;
        } finally {
            fclose($handle);
        }
    }
}
