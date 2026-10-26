<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WeatherStation;
use App\Services\MeasurementCsv;
use App\Services\MeasurementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PhysicalVariableRecordImportController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'admin_colegio', 'docente']), 403);

        return view('admin.physical-variable-record-imports.create', [
            'weatherStations' => $this->visibleWeatherStations($request->user()),
            'preview' => $request->session()->get('measurement_csv_preview'),
        ]);
    }

    public function template(Request $request, MeasurementCsv $csv)
    {
        $data = $request->validate(['weather_station_id' => ['required', 'integer'], 'delimiter' => ['nullable', Rule::in([',', ';'])]]);
        $station = $csv->station($request->user(), $data['weather_station_id']);
        $columns = $csv->columns($station);

        return response()->streamDownload(function () use ($columns, $data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['recorded_at', ...$columns->keys()->all(), 'observations'], $data['delimiter'] ?? ',', '"', '', "\n");
            fclose($output);
        }, 'plantilla_mediciones.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    public function preview(Request $request, MeasurementCsv $csv)
    {
        // Invalid new uploads invalidate an older confirmation too.
        $request->session()->forget('measurement_csv_preview');
        $data = $request->validate([
            'weather_station_id' => ['required', 'integer'],
            'delimiter' => ['required', Rule::in([',', ';'])],
            'csv_file' => ['required', 'file', 'max:2048', 'extensions:csv'],
            'observations' => ['nullable', 'string', 'max:10000'],
        ]);
        $station = $csv->station($request->user(), $data['weather_station_id']);
        $content = file_get_contents($request->file('csv_file')->getRealPath());
        $result = $csv->preview($request->user(), $station, $content, $data['delimiter'], $data);
        $preview = $result + [
            'token' => Str::random(64), 'actor_id' => $request->user()->id,
            'station_id' => $station->id, 'station_name' => $station->name,
            'school_id' => $station->school_id, 'expires_at' => now()->addMinutes(20)->timestamp,
            'content' => $content, 'delimiter' => $data['delimiter'], 'context' => ['observations' => $data['observations'] ?? null],
        ];
        $request->session()->put('measurement_csv_preview', $preview);

        return redirect()->route('admin.physical-variable-record-imports.create')
            ->with('success', $result['errors'] ? 'Vista previa creada: corrige las filas indicadas y vuelve a cargar el archivo.' : 'Vista previa validada. Confirma para guardar los registros.');
    }

    public function commit(Request $request, MeasurementCsv $csv, MeasurementService $measurements)
    {
        $request->validate(['token' => ['required', 'string', 'size:64']]);
        $preview = $request->session()->get('measurement_csv_preview');
        if (! $preview || (int) $preview['actor_id'] !== (int) $request->user()->id
            || $preview['expires_at'] < now()->timestamp || ! hash_equals($preview['token'], $request->string('token')->toString())) {
            throw ValidationException::withMessages(['token' => 'La vista previa expiró o ya fue confirmada. Vuelve a cargar el archivo.']);
        }
        $station = $csv->station($request->user(), $preview['station_id']);
        abort_unless((int) $station->school_id === (int) $preview['school_id'], 403);
        $result = $csv->preview($request->user(), $station, $preview['content'], $preview['delimiter'], $preview['context']);
        if ($result['errors']) {
            throw ValidationException::withMessages(['csv_file' => 'Hay errores o cambió la configuración de las variables. Genera una nueva vista previa.']);
        }
        $count = count($result['rows']);
        DB::transaction(function () use ($request, $preview, $result, $station, $count, $measurements) {
            // A unique receipt makes replay/concurrent confirmations safe across sessions.
            $inserted = DB::table('measurement_import_receipts')->insertOrIgnore([
                'token_hash' => hash('sha256', $preview['token']), 'user_id' => $request->user()->id,
                'school_id' => $station->school_id, 'row_count' => $count, 'created_at' => now(),
            ]);
            if (! $inserted) {
                throw ValidationException::withMessages(['token' => 'Esta importación ya fue confirmada.']);
            }
            foreach ($result['rows'] as $row) {
                $measurements->save($request->user(), $row['input']);
            }
        });
        $request->session()->forget('measurement_csv_preview');

        return redirect()->route('admin.physical-variable-record-imports.create')
            ->with('import_result', ['count' => $count, 'station' => $station->name])
            ->with('success', "Importación completada: {$count} registros guardados. No se omitieron filas.");
    }

    private function visibleWeatherStations(User $actor)
    {
        return WeatherStation::query()->with('school:id,name')->where('is_active', true)
            ->when(! $actor->hasRole('super_admin'), fn ($q) => $q->where('school_id', $actor->school_id ?: -1))
            ->orderBy('school_id')->orderBy('name')->get(['id', 'school_id', 'name', 'code']);
    }
}
