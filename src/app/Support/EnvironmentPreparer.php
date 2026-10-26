<?php

namespace App\Support;

use Dotenv\Dotenv;
use RuntimeException;

final class EnvironmentPreparer
{
    public static function prepare(string $projectDirectory): void
    {
        $environment = $projectDirectory.'/.env';
        $alreadyExists = is_file($environment);
        $handle = fopen($environment, 'c+b');

        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir .env.');
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException('No se pudo bloquear .env para prepararlo.');
            }

            $contents = stream_get_contents($handle);
            if ($contents === false) {
                throw new RuntimeException('No se pudo leer .env.');
            }
            if (! $alreadyExists && $contents === '') {
                $contents = file_get_contents($projectDirectory.'/.env.example');
                if ($contents === false) {
                    throw new RuntimeException('No se pudo leer .env.example.');
                }
            }

            // Parse dotenv syntax, including quotes, export, spacing and CRLF.
            // A configured value must never be rotated by a setup command.
            $values = Dotenv::parse($contents);
            if (isset($values['APP_KEY']) && $values['APP_KEY'] !== '') {
                if (! $alreadyExists && fstat($handle)['size'] === 0) {
                    self::write($handle, $contents);
                }

                return;
            }

            $key = 'base64:'.base64_encode(random_bytes(32));
            $newline = str_contains($contents, "\r\n") ? "\r\n" : "\n";
            $updated = $contents;
            $replacements = 0;
            if (array_key_exists('APP_KEY', $values)) {
                $updated = preg_replace_callback(
                    '/^([ \t]*(?:export[ \t]+)?(?:APP_KEY|\'APP_KEY\'|"APP_KEY")[ \t]*=)[^\r\n]*(\r\n|\n|\r|$)/m',
                    static fn (array $match): string => $match[1].$key.$match[2],
                    $contents,
                    -1,
                    $replacements,
                );
            }
            if ($replacements === 0) {
                $updated .= ($updated !== '' && ! str_ends_with($updated, "\n") ? $newline : '').'APP_KEY='.$key.$newline;
            }

            // Refuse an ambiguous edit rather than modifying another setting,
            // for example an APP_KEY-looking line inside a multiline value.
            $updatedValues = Dotenv::parse($updated);
            if (($updatedValues['APP_KEY'] ?? null) !== $key) {
                throw new RuntimeException('No se pudo establecer APP_KEY de forma segura.');
            }
            unset($values['APP_KEY'], $updatedValues['APP_KEY']);
            if ($values !== $updatedValues) {
                throw new RuntimeException('La preparación modificaría otra configuración. Revisa .env.');
            }

            self::write($handle, $updated);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function write($handle, string $contents): void
    {
        if (! rewind($handle) || ! ftruncate($handle, 0) || fwrite($handle, $contents) !== strlen($contents) || ! fflush($handle)) {
            throw new RuntimeException('No se pudo guardar .env.');
        }
    }
}
