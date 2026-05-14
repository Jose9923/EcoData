<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSensorRequest;
use App\Http\Requests\Admin\UpdateSensorRequest;
use App\Models\PhysicalVariable;
use App\Models\Sensor;
use App\Models\User;
use App\Models\WeatherStation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SensorController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $sensors = Sensor::query()
            ->with(['weatherStation.school', 'variable.category'])
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->whereHas('weatherStation', fn ($q) => $q->where('school_id', $authUser->school_id));
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('brand', 'like', '%' . $search . '%')
                        ->orWhere('model', 'like', '%' . $search . '%')
                        ->orWhere('serial_number', 'like', '%' . $search . '%')
                        ->orWhere('status', 'like', '%' . $search . '%')
                        ->orWhereHas('weatherStation', fn ($q) => $q->where('name', 'like', '%' . $search . '%')->orWhere('code', 'like', '%' . $search . '%'))
                        ->orWhereHas('weatherStation.school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('variable', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy(
                WeatherStation::select('name')
                    ->whereColumn('weather_stations.id', 'sensors.weather_station_id')
                    ->limit(1)
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.sensors.index', compact('sensors', 'search', 'perPage'));
    }

    public function create(Request $request): View
    {
        $authUser = $request->user();

        $selectedStationId = $request->integer('weather_station_id') ?: null;
        $selectedStation = $selectedStationId ? WeatherStation::find($selectedStationId) : null;

        if ($selectedStation) {
            $this->authorizeSchoolScope($authUser, $selectedStation->school_id);
        }

        $selectedSchoolId = $selectedStation?->school_id ?: $this->effectiveSchoolId($request);

        return view('admin.sensors.create', [
            'sensor' => null,
            'stations' => $this->visibleStations($authUser),
            'variables' => $this->visibleVariables($selectedSchoolId),
            'statuses' => $this->statuses(),
            'selectedStationId' => $selectedStationId,
        ]);
    }

    public function store(StoreSensorRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $station = WeatherStation::findOrFail($data['weather_station_id']);

        $this->authorizeSchoolScope($authUser, $station->school_id);

        Sensor::create([
            'weather_station_id' => (int) $data['weather_station_id'],
            'physical_variable_id' => $data['physical_variable_id'] ?? null,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'brand' => filled($data['brand'] ?? null) ? trim($data['brand']) : null,
            'model' => filled($data['model'] ?? null) ? trim($data['model']) : null,
            'serial_number' => filled($data['serial_number'] ?? null) ? trim($data['serial_number']) : null,
            'measurement_unit' => filled($data['measurement_unit'] ?? null) ? trim($data['measurement_unit']) : null,
            'measurement_range' => filled($data['measurement_range'] ?? null) ? trim($data['measurement_range']) : null,
            'accuracy' => filled($data['accuracy'] ?? null) ? trim($data['accuracy']) : null,
            'installation_date' => $data['installation_date'] ?? null,
            'last_maintenance_date' => $data['last_maintenance_date'] ?? null,
            'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
            'status' => $data['status'],
            'observations' => filled($data['observations'] ?? null) ? trim($data['observations']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.sensors.index')
            ->with('success', 'Sensor creado correctamente.');
    }

    public function show(Request $request, Sensor $sensor): View
    {
        $authUser = $request->user();

        $sensor->load(['weatherStation.school', 'variable.category']);

        $this->authorizeSchoolScope($authUser, $sensor->weatherStation?->school_id);

        return view('admin.sensors.show', compact('sensor'));
    }

    public function edit(Request $request, Sensor $sensor): View
    {
        $authUser = $request->user();

        $sensor->load(['weatherStation.school', 'variable']);

        $this->authorizeSchoolScope($authUser, $sensor->weatherStation?->school_id);

        $selectedStationId = old('weather_station_id', $request->integer('weather_station_id') ?: $sensor->weather_station_id);
        $selectedStation = WeatherStation::find($selectedStationId);

        $selectedSchoolId = $selectedStation?->school_id ?: $sensor->weatherStation?->school_id;

        return view('admin.sensors.edit', [
            'sensor' => $sensor,
            'stations' => $this->visibleStations($authUser),
            'variables' => $this->visibleVariables($selectedSchoolId),
            'statuses' => $this->statuses(),
            'selectedStationId' => $selectedStationId,
        ]);
    }

    public function update(UpdateSensorRequest $request, Sensor $sensor): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $currentStation = $sensor->weatherStation;
        $newStation = WeatherStation::findOrFail($data['weather_station_id']);

        $this->authorizeSchoolScope($authUser, $currentStation?->school_id);
        $this->authorizeSchoolScope($authUser, $newStation->school_id);

        $sensor->update([
            'weather_station_id' => (int) $data['weather_station_id'],
            'physical_variable_id' => $data['physical_variable_id'] ?? null,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'brand' => filled($data['brand'] ?? null) ? trim($data['brand']) : null,
            'model' => filled($data['model'] ?? null) ? trim($data['model']) : null,
            'serial_number' => filled($data['serial_number'] ?? null) ? trim($data['serial_number']) : null,
            'measurement_unit' => filled($data['measurement_unit'] ?? null) ? trim($data['measurement_unit']) : null,
            'measurement_range' => filled($data['measurement_range'] ?? null) ? trim($data['measurement_range']) : null,
            'accuracy' => filled($data['accuracy'] ?? null) ? trim($data['accuracy']) : null,
            'installation_date' => $data['installation_date'] ?? null,
            'last_maintenance_date' => $data['last_maintenance_date'] ?? null,
            'next_maintenance_date' => $data['next_maintenance_date'] ?? null,
            'status' => $data['status'],
            'observations' => filled($data['observations'] ?? null) ? trim($data['observations']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.sensors.index')
            ->with('success', 'Sensor actualizado correctamente.');
    }

    public function destroy(Request $request, Sensor $sensor): RedirectResponse
    {
        $authUser = $request->user();

        $sensor->load('weatherStation');

        $this->authorizeSchoolScope($authUser, $sensor->weatherStation?->school_id);

        $sensor->delete();

        return redirect()
            ->route('admin.sensors.index')
            ->with('success', 'Sensor eliminado correctamente.');
    }

    private function visibleStations(User $authUser)
    {
        return WeatherStation::query()
            ->with('school:id,name')
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('school_id', $authUser->school_id))
            ->orderBy('school_id')
            ->orderBy('name')
            ->get(['id', 'school_id', 'name', 'code']);
    }

    private function visibleVariables(?int $schoolId)
    {
        return PhysicalVariable::query()
            ->with('category:id,name')
            ->when($schoolId, fn ($query) => $query->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get(['id', 'school_id', 'category_id', 'name', 'unit']);
    }

    private function effectiveSchoolId(Request $request): ?int
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('super_admin')) {
            abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');
            return (int) $authUser->school_id;
        }

        return null;
    }

    private function statuses(): array
    {
        return [
            'operativo' => 'Operativo',
            'mantenimiento' => 'En mantenimiento',
            'fallando' => 'Fallando',
            'inactivo' => 'Inactivo',
            'retirado' => 'Retirado',
        ];
    }

    private function authorizeSchoolScope(User $authUser, ?int $schoolId): void
    {
        if ($authUser->hasRole('super_admin')) {
            return;
        }

        abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

        abort_if(
            (int) $schoolId !== (int) $authUser->school_id,
            403,
            'No tienes autorización para gestionar sensores de otro colegio.'
        );
    }
}