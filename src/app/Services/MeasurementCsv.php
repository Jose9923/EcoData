<?php

namespace App\Services;

use App\Models\PhysicalVariable;
use App\Models\User;
use App\Models\WeatherStation;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class MeasurementCsv
{
    public const MAX_ROWS = 500;

    public const MAX_BYTES = 2097152;

    public function station(User $actor, int $id): WeatherStation
    {
        abort_unless($actor->hasAnyRole(['super_admin', 'admin_colegio', 'docente']), 403);

        return WeatherStation::query()->where('is_active', true)
            ->when(! $actor->hasRole('super_admin'), fn ($q) => $q->where('school_id', $actor->school_id ?: -1))
            ->findOrFail($id);
    }

    public function columns(WeatherStation $station): Collection
    {
        $variables = PhysicalVariable::query()->where('school_id', $station->school_id)->where('is_active', true)
            ->whereHas('sensors', fn ($q) => $q->where('weather_station_id', $station->id)->where('is_active', true))
            ->orderBy('id')->get();
        if ($variables->isEmpty() || $variables->count() > 100) {
            throw ValidationException::withMessages(['weather_station_id' => 'La estación debe tener entre 1 y 100 variables activas vinculadas a sensores activos.']);
        }

        return $variables->mapWithKeys(fn ($v) => [self::safeCell($v->slug.' ['.($v->unit ?: '-').']') => $v]);
    }

    public static function safeCell(string $value): string
    {
        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
    }

    /** No database mutations. Returns all row errors so a preview cannot silently skip data. */
    public function preview(User $actor, WeatherStation $station, string $content, string $delimiter, array $context = []): array
    {
        if (! in_array($delimiter, [',', ';'], true) || strlen($content) > self::MAX_BYTES || str_contains($content, "\0") || ! mb_check_encoding($content, 'UTF-8')) {
            throw ValidationException::withMessages(['csv_file' => 'Usa CSV UTF-8 de máximo 2 MiB, separado por coma o punto y coma.']);
        }
        $columns = $this->columns($station);
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, preg_replace('/^\xEF\xBB\xBF/', '', $content));
        rewind($stream);
        try {
            $headers = fgetcsv($stream, null, $delimiter, '"', '');
            if (! is_array($headers) || count($headers) > 102 || count(array_unique($headers)) !== count($headers) || ! in_array('recorded_at', $headers, true)) {
                throw ValidationException::withMessages(['csv_file' => 'Cabecera inválida: incluye recorded_at una sola vez y evita columnas duplicadas.']);
            }
            $unknown = array_diff($headers, ['recorded_at', 'observations'], $columns->keys()->all());
            if ($unknown || ! array_intersect($headers, $columns->keys()->all())) {
                throw ValidationException::withMessages(['csv_file' => 'Las columnas/unidades no coinciden con la plantilla vigente de esta estación. Descarga nuevamente la plantilla.']);
            }
            $rows = [];
            $errors = [];
            $line = 1;
            while (($cells = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
                $line++;
                if ($cells === [null]) {
                    continue;
                }
                if (count($rows) >= self::MAX_ROWS) {
                    throw ValidationException::withMessages(['csv_file' => 'El máximo es de '.self::MAX_ROWS.' filas por importación.']);
                }
                if (count($cells) !== count($headers)) {
                    $rows[] = ['line' => $line, 'input' => null];
                    $errors[$line] = ['La cantidad de columnas no coincide con la cabecera.'];

                    continue;
                }
                $row = array_combine($headers, $cells);
                $values = [];
                foreach ($columns as $header => $variable) {
                    if (array_key_exists($header, $row)) {
                        $values[$variable->id] = $row[$header];
                    }
                }
                $input = [
                    'school_id' => $station->school_id,
                    'weather_station_id' => $station->id,
                    'source_type' => 'csv',
                    'grade_id' => $context['grade_id'] ?? null,
                    'course_id' => $context['course_id'] ?? null,
                    'recorded_at' => $row['recorded_at'],
                    'observations' => $row['observations'] ?? $context['observations'] ?? null,
                    'values' => $values,
                ];
                $rows[] = ['line' => $line, 'input' => $input];
                try {
                    app(MeasurementService::class)->prepare($actor, $input);
                } catch (ValidationException $error) {
                    $errors[$line] = array_merge(...array_values($error->errors()));
                }
            }
            if (! $rows) {
                throw ValidationException::withMessages(['csv_file' => 'El archivo no contiene filas de datos.']);
            }

            return ['headers' => $headers, 'rows' => $rows, 'errors' => $errors, 'columns' => $columns->mapWithKeys(fn ($v) => [$v->id => ['name' => $v->name, 'unit' => $v->unit]])->all()];
        } finally {
            fclose($stream);
        }
    }
}
