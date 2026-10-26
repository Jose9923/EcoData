<?php

namespace App\Services;

use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableRecord;
use App\Models\PhysicalVariableRecordValue;
use App\Models\User;
use App\Policies\PhysicalVariableRecordPolicy;
use App\Support\MeasurementDecimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class MeasurementService
{
    /** Validate the same context and typed values for forms and CSV, without writing. */
    public function prepare(User $actor, array $input, ?PhysicalVariableRecord $record = null): array
    {
        abort_unless($actor->hasAnyRole(['super_admin', 'admin_colegio', 'docente', 'estudiante']), 403);
        $schoolId = $record?->school_id ?? ($actor->hasRole('super_admin') ? ($input['school_id'] ?? null) : $actor->school_id);
        abort_unless($schoolId && ($actor->hasRole('super_admin') || (int) $schoolId === (int) $actor->school_id), 403);
        if ($record) {
            abort_unless((new PhysicalVariableRecordPolicy)->update($actor, $record), 403);
            if (isset($input['school_id']) && (int) $input['school_id'] !== (int) $record->school_id) {
                throw ValidationException::withMessages(['school_id' => 'El colegio del registro no se puede cambiar.']);
            }
        } elseif (isset($input['school_id']) && (int) $input['school_id'] !== (int) $schoolId) {
            throw ValidationException::withMessages(['school_id' => 'El colegio seleccionado no está autorizado.']);
        }
        $input['school_id'] = $schoolId;
        if ($actor->hasRole('estudiante')) {
            $input['grade_id'] = $record ? $record->grade_id : $actor->grade_id;
            $input['course_id'] = $record ? $record->course_id : $actor->course_id;
        }
        $input += ['grade_id' => null, 'course_id' => null, 'weather_station_id' => null, 'observations' => null, 'values' => [], 'remove_values' => []];
        $rules = [
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')],
            'grade_id' => ['nullable', 'integer', Rule::exists('grades', 'id')->where('school_id', $schoolId)],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')->where('school_id', $schoolId)->where('grade_id', $input['grade_id'])],
            'weather_station_id' => ['nullable', 'integer', Rule::exists('weather_stations', 'id')->where(function ($query) use ($schoolId, $record) {
                $query->where('school_id', $schoolId)->where(function ($station) use ($record) {
                    $station->where('is_active', true);
                    if ($record?->weather_station_id) {
                        $station->orWhere('id', $record->weather_station_id);
                    }
                });
            })],
            'source_type' => ['required', Rule::in(['manual', 'station', 'csv'])],
            'recorded_at' => ['required', 'date_format:Y-m-d\\TH:i,Y-m-d H:i,Y-m-d H:i:s'],
            'observations' => ['nullable', 'string', 'max:10000'],
            'values' => ['array', 'max:200'],
            'remove_values' => ['array', 'max:200'],
            'remove_values.*' => ['integer', 'distinct'],
        ];
        if (in_array($input['source_type'] ?? '', ['station', 'csv'], true)) {
            $rules['weather_station_id'][] = 'required';
        }
        $validated = Validator::make($input, $rules, [
            'course_id.exists' => 'El curso debe pertenecer al colegio y grado seleccionados.',
            'weather_station_id.exists' => 'La estación no pertenece al colegio o está inactiva.',
            'weather_station_id.required' => 'Selecciona una estación para este origen de datos.',
            'recorded_at.date_format' => 'Usa fecha y hora YYYY-MM-DD HH:MM (segundos opcionales).',
        ])->validate();
        $existing = $record?->values()->get()->keyBy('physical_variable_id') ?? collect();
        $ids = array_keys($validated['values']);
        foreach ($ids as $id) {
            if (! ctype_digit((string) $id) || (int) $id < 1) {
                throw ValidationException::withMessages(['values' => 'Identificador de variable inválido.']);
            }
        }
        $variables = PhysicalVariable::query()->where('school_id', $schoolId)
            ->whereIn('id', $ids)->where(function ($query) use ($existing) {
                $query->where('is_active', true)->orWhereIn('id', $existing->keys());
            })->get()->keyBy('id');
        $payloads = [];
        $errors = [];
        foreach ($validated['values'] as $id => $raw) {
            $variable = $variables->get($id);
            if (! $variable) {
                $errors["values.$id"] = 'La variable no está disponible en este colegio.';

                continue;
            }
            if ($raw === null || $raw === '' || (is_string($raw) && trim($raw) === '')) {
                continue; // Empty/omitted inputs never delete previously captured values.
            }
            try {
                // Historical values remain valid even when today's configured range/precision changed.
                $previous = $existing->get($id);
                if ($previous && $this->isUnchanged($variable, $previous, $raw)) {
                    continue;
                }
                $payloads[$id] = $this->typedValue($variable, $raw);
            } catch (InvalidArgumentException $exception) {
                $errors["values.$id"] = $variable->name.': '.$exception->getMessage();
            }
        }
        $remove = array_map('intval', $validated['remove_values']);
        foreach ($remove as $id) {
            if (! $existing->has($id)) {
                $errors['remove_values'] = 'Solo puedes retirar valores que pertenecen a este registro.';
            }
            unset($payloads[$id]);
        }
        $remaining = $existing->keys()->diff($remove)->merge(array_keys($payloads))->unique();
        if ($remaining->isEmpty()) {
            $errors['values'] = 'Debes conservar al menos una variable física.';
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return ['context' => collect($validated)->only(['school_id', 'grade_id', 'course_id', 'weather_station_id', 'source_type', 'recorded_at', 'observations'])->all(), 'values' => $payloads, 'remove' => $remove];
    }

    private function isUnchanged(PhysicalVariable $variable, PhysicalVariableRecordValue $previous, mixed $raw): bool
    {
        return match ($variable->data_type) {
            'integer', 'decimal' => MeasurementDecimal::normalize($raw) === MeasurementDecimal::normalize($previous->value_numeric),
            'text' => is_string($raw) && trim($raw) === $previous->value_text,
            'boolean' => in_array($raw, [true, false, 0, 1, '0', '1'], true) && (bool) $raw === $previous->value_boolean,
            'date' => is_string($raw) && $raw === $previous->value_date?->format('Y-m-d'),
            default => false,
        };
    }

    private function typedValue(PhysicalVariable $variable, mixed $raw): array
    {
        $payload = ['value_numeric' => null, 'value_text' => null, 'value_boolean' => null, 'value_date' => null];
        switch ($variable->data_type) {
            case 'integer':
            case 'decimal':
                $number = MeasurementDecimal::normalize($raw, $variable->data_type === 'integer' ? 0 : min(4, (int) $variable->decimals));
                if ($variable->min_value !== null && MeasurementDecimal::compare($number, $variable->min_value) < 0) {
                    throw new InvalidArgumentException('El mínimo es '.$variable->min_value.'.');
                }
                if ($variable->max_value !== null && MeasurementDecimal::compare($number, $variable->max_value) > 0) {
                    throw new InvalidArgumentException('El máximo es '.$variable->max_value.'.');
                }
                $payload['value_numeric'] = $number;
                break;
            case 'text':
                if (! is_string($raw) || mb_strlen($raw) > 10000) {
                    throw new InvalidArgumentException('Debe ser texto de hasta 10000 caracteres.');
                }
                $payload['value_text'] = trim($raw);
                break;
            case 'boolean':
                if (! in_array($raw, [true, false, 0, 1, '0', '1'], true)) {
                    throw new InvalidArgumentException('Usa 1 para sí o 0 para no.');
                }
                $payload['value_boolean'] = (bool) $raw;
                break;
            case 'date':
                if (! is_string($raw) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $raw) || ! checkdate((int) substr($raw, 5, 2), (int) substr($raw, 8, 2), (int) substr($raw, 0, 4))) {
                    throw new InvalidArgumentException('Usa una fecha válida YYYY-MM-DD.');
                }
                $payload['value_date'] = $raw;
                break;
            default:
                throw new InvalidArgumentException('Tipo de variable no compatible.');
        }

        return $payload;
    }

    public function save(User $actor, array $input, ?PhysicalVariableRecord $record = null): PhysicalVariableRecord
    {
        return DB::transaction(function () use ($actor, $input, $record) {
            if ($record) {
                $record = PhysicalVariableRecord::query()->lockForUpdate()->findOrFail($record->id);
            }
            $prepared = $this->prepare($actor, $input, $record);
            $before = $record ? $this->snapshot($record) : null;
            if ($record) {
                $record->update($prepared['context']);
            } else {
                $record = PhysicalVariableRecord::create($prepared['context'] + ['user_id' => $actor->id]);
            }
            foreach ($prepared['values'] as $variableId => $payload) {
                $record->values()->updateOrCreate(['physical_variable_id' => $variableId], $payload);
            }
            if ($prepared['remove']) {
                $record->values()->whereIn('physical_variable_id', $prepared['remove'])->delete();
            }
            $after = $this->snapshot($record->refresh());
            if ($before !== $after) {
                $record->audits()->create(['user_id' => $actor->id, 'action' => $before ? 'updated' : 'created', 'before' => $before, 'after' => $after, 'created_at' => now()]);
            }

            return $record;
        });
    }

    private function snapshot(PhysicalVariableRecord $record): array
    {
        return [
            'context' => array_merge($record->only(['school_id', 'grade_id', 'course_id', 'weather_station_id', 'source_type', 'observations']), ['recorded_at' => $record->recorded_at?->format('Y-m-d H:i:s')]),
            'values' => $record->values()->with('variable')->orderBy('physical_variable_id')->get()->mapWithKeys(fn ($value) => [
                $value->physical_variable_id => ['name' => $value->variable?->name, 'unit' => $value->variable?->unit, 'value' => $value->value_numeric ?? $value->value_text ?? $value->value_boolean ?? $value->value_date?->format('Y-m-d')],
            ])->all(),
        ];
    }
}
