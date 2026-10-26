<?php

namespace App\Services;

use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableRecord;
use App\Models\User;
use App\Models\WeatherStation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PhysicalRecordReports
{
    public function authorize(User $user): void
    {
        abort_unless($user->hasAnyRole(['super_admin', 'admin_colegio', 'docente', 'estudiante']), 403);
        abort_if(! $user->hasRole('super_admin') && ! $user->school_id, 403);
    }

    public function variables(User $user): Builder
    {
        return PhysicalVariable::query()
            ->when(! $user->hasRole('super_admin'), fn ($query) => $query->where('school_id', $user->school_id));
    }

    public function stations(User $user): Builder
    {
        return WeatherStation::query()
            ->when(! $user->hasRole('super_admin'), fn ($query) => $query->where('school_id', $user->school_id));
    }

    public function filters(Request $request, bool $numericOnly = false): array
    {
        $this->authorize($request->user());
        $data = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'variable_id' => ['nullable', 'integer', 'min:1'],
            'weather_station_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $data['date_to'] = $data['date_to'] ?? today()->toDateString();
        $data['date_from'] = $data['date_from'] ?? Carbon::parse($data['date_to'])->subDays(29)->toDateString();
        $from = Carbon::parse($data['date_from']);
        $to = Carbon::parse($data['date_to']);
        if ($from->gt($to) || $from->diffInDays($to) > 365) {
            throw ValidationException::withMessages(['date_to' => 'Selecciona un período de 1 a 366 días, con inicio anterior al final.']);
        }
        if (! empty($data['variable_id'])) {
            $variable = $this->variables($request->user())->find($data['variable_id']);
            if (! $variable || ($numericOnly && ! in_array($variable->data_type, ['integer', 'decimal'], true))) {
                throw ValidationException::withMessages(['variable_id' => 'Selecciona una variable disponible para tu cuenta.']);
            }
        }
        if (! empty($data['weather_station_id']) && ! $this->stations($request->user())->whereKey($data['weather_station_id'])->exists()) {
            throw ValidationException::withMessages(['weather_station_id' => 'Selecciona una estación disponible para tu cuenta.']);
        }

        return $data;
    }

    public function query(User $user, array $filters): Builder
    {
        $this->authorize($user);

        return PhysicalVariableRecord::query()->visibleTo($user)
            ->where('recorded_at', '>=', Carbon::parse($filters['date_from'])->startOfDay())
            ->where('recorded_at', '<', Carbon::parse($filters['date_to'])->addDay()->startOfDay())
            ->when($filters['weather_station_id'] ?? null, fn ($query, $station) => $query->where('weather_station_id', $station))
            ->when($filters['variable_id'] ?? null, fn ($query, $variable) => $query->whereHas('values', fn ($values) => $values->where('physical_variable_id', $variable)));
    }
}
