<?php

namespace App\Http\Controllers;

use App\Services\MeasurementCharts;
use App\Services\PhysicalRecordReports;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PhysicalRecordAnalyticsController extends Controller
{
    public function __invoke(Request $request, PhysicalRecordReports $reports, MeasurementCharts $charts): View
    {
        $user = $request->user();
        $filters = $reports->filters($request, true);
        $display = $request->validate([
            'group_by' => ['nullable', Rule::in(['day', 'week', 'month'])],
            'chart_type' => ['nullable', Rule::in(['line', 'bar'])],
        ]);
        $filters['group_by'] = $display['group_by'] ?? 'day';
        $filters['chart_type'] = $display['chart_type'] ?? 'line';
        $variables = $reports->variables($user)->whereIn('data_type', ['integer', 'decimal'])->with('school')->orderBy('name')->get();
        $variable = $variables->firstWhere('id', $filters['variable_id'] ?? null);
        if ($variable && ! empty($filters['weather_station_id'])) {
            $matchesSchool = $reports->stations($user)
                ->where('school_id', $variable->school_id)
                ->whereKey($filters['weather_station_id'])->exists();
            if (! $matchesSchool) {
                throw ValidationException::withMessages([
                    'weather_station_id' => 'Selecciona una estación del mismo colegio que la variable.',
                ]);
            }
        }

        $analytics = $variable ? $charts->analyze($user, $variable, $filters) : [
            'series' => collect(),
            'current' => null,
            'previous' => null,
            'previousFilters' => $filters,
            'outside' => 0,
            'chartData' => null,
        ];

        return view('reports.analytics', [
            'filters' => $filters,
            'variables' => $variables,
            'variable' => $variable,
            'stations' => $reports->stations($user)
                ->when($variable, fn ($query) => $query->where('school_id', $variable->school_id))
                ->with('school')->orderBy('name')->get(),
            ...$analytics,
        ]);
    }
}
