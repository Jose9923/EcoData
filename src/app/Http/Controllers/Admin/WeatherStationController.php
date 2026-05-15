<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWeatherStationRequest;
use App\Http\Requests\Admin\UpdateWeatherStationRequest;
use App\Models\School;
use App\Models\User;
use App\Models\WeatherStation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class WeatherStationController extends Controller
{
    public function index(Request $request): View
    {
        $authUser = $request->user();

        $search = (string) $request->string('search')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        if (! in_array($perPage, [10, 15, 25, 50], true)) {
            $perPage = 10;
        }

        $stations = WeatherStation::query()
            ->with(['school', 'responsible'])
            ->when(! $authUser->hasRole('super_admin'), function ($query) use ($authUser) {
                $query->where('school_id', $authUser->school_id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('location_name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhereHas('school', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('responsible', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->orderBy('school_id')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return view('admin.weather-stations.index', compact('stations', 'search', 'perPage'));
    }

    public function create(Request $request): View
    {
        $authUser = $request->user();

        $selectedSchoolId = $authUser->hasRole('super_admin')
            ? ($request->integer('school_id') ?: null)
            : (int) $authUser->school_id;

        return view('admin.weather-stations.create', [
            'schools' => $this->visibleSchools($authUser),
            'responsibles' => $this->visibleResponsibles($selectedSchoolId),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedResponsibleId' => $request->integer('responsible_user_id') ?: null,
        ]);
    }

    public function store(StoreWeatherStationRequest $request): RedirectResponse
    {
        $authUser = $request->user();
        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        WeatherStation::create([
            'school_id' => $schoolId,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'location_name' => filled($data['location_name'] ?? null) ? trim($data['location_name']) : null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'altitude' => $data['altitude'] ?? null,
            'installation_date' => $data['installation_date'] ?? null,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.weather-stations.index')
            ->with('success', 'Estación meteorológica creada correctamente.');
    }

    public function show(Request $request, WeatherStation $weather_station): View
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $weather_station->school_id);

        $weather_station->load(['school', 'responsible']);

        return view('admin.weather-stations.show', [
            'station' => $weather_station,
        ]);
    }

    public function edit(Request $request, WeatherStation $weather_station): View
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $weather_station->school_id);

        $selectedSchoolId = $authUser->hasRole('super_admin')
            ? old('school_id', $request->integer('school_id') ?: $weather_station->school_id)
            : (int) $authUser->school_id;

        return view('admin.weather-stations.edit', [
            'station' => $weather_station,
            'schools' => $this->visibleSchools($authUser),
            'responsibles' => $this->visibleResponsibles((int) $selectedSchoolId),
            'selectedSchoolId' => $selectedSchoolId,
            'selectedResponsibleId' => old('responsible_user_id', $weather_station->responsible_user_id),
        ]);
    }

    public function update(UpdateWeatherStationRequest $request, WeatherStation $weather_station): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $weather_station->school_id);

        $data = $request->validated();

        $schoolId = $authUser->hasRole('super_admin')
            ? (int) $data['school_id']
            : (int) $authUser->school_id;

        $weather_station->update([
            'school_id' => $schoolId,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'name' => trim($data['name']),
            'code' => trim($data['code']),
            'location_name' => filled($data['location_name'] ?? null) ? trim($data['location_name']) : null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'altitude' => $data['altitude'] ?? null,
            'installation_date' => $data['installation_date'] ?? null,
            'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return redirect()
            ->route('admin.weather-stations.index')
            ->with('success', 'Estación meteorológica actualizada correctamente.');
    }

    public function destroy(Request $request, WeatherStation $weather_station): RedirectResponse
    {
        $authUser = $request->user();

        $this->authorizeSchoolScope($authUser, $weather_station->school_id);

        $weather_station->delete();

        return redirect()
            ->route('admin.weather-stations.index')
            ->with('success', 'Estación meteorológica eliminada correctamente.');
    }

    private function visibleSchools(User $authUser)
    {
        return School::query()
            ->where('is_active', true)
            ->when(! $authUser->hasRole('super_admin'), fn ($query) => $query->where('id', $authUser->school_id))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function visibleResponsibles(?int $schoolId)
    {
        if (! $schoolId) {
            return collect();
        }

        return User::query()
            ->where('school_id', $schoolId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin_colegio', 'docente']))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'school_id']);
    }

    private function resolveSelectedSchoolId(Request $request): ?int
    {
        $authUser = $request->user();

        if (! $authUser->hasRole('super_admin')) {
            abort_if(! $authUser->school_id, 403, 'Tu usuario no tiene un colegio asignado.');

            return (int) $authUser->school_id;
        }

        return $request->integer('school_id') ?: null;
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
            'No tienes autorización para gestionar estaciones meteorológicas de otro colegio.'
        );
    }

    public function getResponsibles(Request $request): JsonResponse
    {
        $authUser = $request->user();

        $schoolId = $authUser->hasRole('super_admin')
            ? $request->integer('school_id')
            : (int) $authUser->school_id;

        abort_if(! $schoolId, 422, 'Debes seleccionar un colegio.');

        if (! $authUser->hasRole('super_admin')) {
            abort_if(
                (int) $schoolId !== (int) $authUser->school_id,
                403,
                'No puedes consultar responsables de otro colegio.'
            );
        }

        $responsibles = $this->visibleResponsibles($schoolId)
            ->map(fn ($user) => [
                'id' => $user->id,
                'label' => "{$user->name} — {$user->email}",
            ])
            ->values();

        return response()->json($responsibles);
    }
}