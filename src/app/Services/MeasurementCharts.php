<?php

namespace App\Services;

use App\Models\PhysicalVariable;
use App\Models\PhysicalVariableRecordValue;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MeasurementCharts
{
    public function __construct(private readonly PhysicalRecordReports $reports) {}

    public function analyze(User $user, PhysicalVariable $variable, array $filters): array
    {
        $from = Carbon::parse($filters['date_from'])->startOfDay();
        $days = (int) $from->diffInDays(Carbon::parse($filters['date_to'])) + 1;
        $previousFilters = array_replace($filters, [
            'date_from' => $from->copy()->subDays($days)->toDateString(),
            'date_to' => $from->copy()->subDay()->toDateString(),
        ]);
        $currentQuery = $this->values($user, $variable, $filters);
        $previousQuery = $this->values($user, $variable, $previousFilters);
        $current = $this->stats(clone $currentQuery);
        $previous = $this->stats(clone $previousQuery);
        $series = $this->daily(clone $currentQuery);
        $previousSeries = $this->daily(clone $previousQuery)->keyBy('day');
        $daily = $series->keyBy('day');
        $outside = $this->outside(clone $currentQuery, $variable);
        $trend = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $from->copy()->addDays($offset);
            $date = $day->toDateString();
            $previousDate = $day->copy()->subDays($days)->toDateString();
            $point = $daily->get($date);
            $previousPoint = $previousSeries->get($previousDate);
            $trend[] = [
                'date' => $date,
                'average' => $this->number($point?->average),
                'minimum' => $this->number($point?->minimum),
                'maximum' => $this->number($point?->maximum),
                'count' => (int) ($point?->count ?? 0),
                'previousDate' => $previousDate,
                'previousAverage' => $this->number($previousPoint?->average),
                'previousMinimum' => $this->number($previousPoint?->minimum),
                'previousMaximum' => $this->number($previousPoint?->maximum),
                'previousCount' => (int) ($previousPoint?->count ?? 0),
            ];
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'series' => $series,
            'previousFilters' => $previousFilters,
            'outside' => $outside,
            'chartData' => [
                'variable' => [
                    'id' => $variable->id,
                    'name' => $variable->name,
                    'unit' => $variable->unit,
                    'decimals' => min(4, max(0, $variable->decimals ?? 0)),
                    'min' => $this->number($variable->min_value),
                    'max' => $this->number($variable->max_value),
                ],
                'range' => [
                    'from' => $filters['date_from'],
                    'to' => $filters['date_to'],
                    'previousFrom' => $previousFilters['date_from'],
                    'previousTo' => $previousFilters['date_to'],
                ],
                'trend' => $trend,
                'stations' => $this->stations(clone $currentQuery),
                'distribution' => $this->distribution(clone $currentQuery, $current),
                'summary' => [
                    'count' => (int) $current->count,
                    'average' => $this->number($current->average),
                    'minimum' => $this->number($current->minimum),
                    'maximum' => $this->number($current->maximum),
                    'outside' => $outside,
                    'previousCount' => (int) $previous->count,
                    'previousAverage' => $this->number($previous->average),
                    'difference' => $current->count && $previous->count
                        ? (float) $current->average - (float) $previous->average
                        : null,
                ],
            ],
        ];
    }

    private function values(User $user, PhysicalVariable $variable, array $filters): Builder
    {
        return PhysicalVariableRecordValue::query()
            ->join('physical_variable_records as records', 'records.id', '=', 'physical_variable_record_values.physical_variable_record_id')
            ->whereIn('records.id', $this->reports->query($user, $filters)->select('physical_variable_records.id'))
            ->where('records.school_id', $variable->school_id)
            ->where('physical_variable_id', $variable->id)
            ->whereNotNull('value_numeric')
            ->toBase();
    }

    private function stats(Builder $query): object
    {
        return $query->selectRaw('COUNT(*) as count, AVG(value_numeric) as average, MIN(value_numeric) as minimum, MAX(value_numeric) as maximum')->first();
    }

    private function daily(Builder $query): Collection
    {
        return $query->selectRaw('DATE(records.recorded_at) as day, AVG(value_numeric) as average, MIN(value_numeric) as minimum, MAX(value_numeric) as maximum, COUNT(*) as count')
            ->groupByRaw('DATE(records.recorded_at)')->orderBy('day')->get();
    }

    private function outside(Builder $query, PhysicalVariable $variable): int
    {
        if ($variable->min_value === null && $variable->max_value === null) {
            return 0;
        }

        return $query->where(function (Builder $query) use ($variable) {
            if ($variable->min_value !== null) {
                $query->orWhere('value_numeric', '<', $variable->min_value);
            }
            if ($variable->max_value !== null) {
                $query->orWhere('value_numeric', '>', $variable->max_value);
            }
        })->count();
    }

    private function stations(Builder $query): array
    {
        return $query->leftJoin('weather_stations as stations', function ($join) {
            $join->on('stations.id', '=', 'records.weather_station_id')
                ->on('stations.school_id', '=', 'records.school_id');
        })
            ->selectRaw('stations.id, stations.name, COUNT(*) as count, AVG(value_numeric) as average, MIN(value_numeric) as minimum, MAX(value_numeric) as maximum')
            ->groupBy('stations.id', 'stations.name')->orderBy('stations.name')->get()
            ->map(fn ($station) => [
                'id' => $station->id === null ? null : (int) $station->id,
                'name' => $station->name ?? 'Sin estación',
                'count' => (int) $station->count,
                'average' => $this->number($station->average),
                'minimum' => $this->number($station->minimum),
                'maximum' => $this->number($station->maximum),
            ])->all();
    }

    private function distribution(Builder $query, object $stats): array
    {
        if (! $stats->count) {
            return [];
        }

        // Integer ten-thousandths keep adjacent DECIMAL(15,4) boundaries exact.
        $minimum = $this->scaled($stats->minimum);
        $maximum = $this->scaled($stats->maximum);
        if ($minimum === $maximum) {
            return [[
                'from' => $this->number($stats->minimum),
                'to' => $this->number($stats->maximum),
                'count' => (int) $stats->count,
            ]];
        }

        $width = intdiv($maximum - $minimum + 9, 10);
        $bins = [];
        $expressions = [];
        $bindings = [];
        for ($lower = $minimum; $lower < $maximum; $lower += $width) {
            $upper = min($maximum, $lower + $width);
            $index = count($bins);
            $operator = $upper === $maximum ? '<=' : '<';
            $expressions[] = "SUM(CASE WHEN value_numeric >= ? AND value_numeric {$operator} ? THEN 1 ELSE 0 END) as bucket_{$index}";
            $bindings[] = $this->decimal($lower);
            $bindings[] = $this->decimal($upper);
            $bins[] = ['from' => $lower / 10000, 'to' => $upper / 10000];
        }
        $counts = $query->selectRaw(implode(', ', $expressions), $bindings)->first();

        return array_map(fn ($bin, $index) => $bin + ['count' => (int) $counts->{'bucket_'.$index}], $bins, array_keys($bins));
    }

    private function scaled(mixed $value): int
    {
        return (int) str_replace('.', '', number_format((float) $value, 4, '.', ''));
    }

    private function decimal(int $value): string
    {
        $absolute = abs($value);

        return ($value < 0 ? '-' : '').intdiv($absolute, 10000).'.'.str_pad((string) ($absolute % 10000), 4, '0', STR_PAD_LEFT);
    }

    private function number(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
