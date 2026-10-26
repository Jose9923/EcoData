<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$db = \Illuminate\Support\Facades\DB::connection();
$db->statement('SET TRANSACTION READ ONLY');
$db->beginTransaction();
try {
    $actor = \App\Models\User::role('super_admin')->first();
    $variable = \App\Models\PhysicalVariable::query()
        ->whereIn('data_type', ['integer', 'decimal'])
        ->whereHas('recordValues', fn ($query) => $query->whereNotNull('value_numeric'))
        ->first();

    if (! $actor || ! $variable) {
        echo json_encode(['status' => 'skipped', 'reason' => 'No privileged actor or numeric measurements available']).PHP_EOL;
        exit(0);
    }

    $dates = $db->table('physical_variable_record_values as values')
        ->join('physical_variable_records as records', 'records.id', '=', 'values.physical_variable_record_id')
        ->where('values.physical_variable_id', $variable->id)
        ->where('records.school_id', $variable->school_id)
        ->whereNotNull('values.value_numeric')
        ->selectRaw('MIN(records.recorded_at) as first_date, MAX(records.recorded_at) as last_date')
        ->first();
    if (! $dates->last_date) {
        echo json_encode(['status' => 'skipped', 'reason' => 'No consistent school measurements available']).PHP_EOL;
        exit(0);
    }

    $to = \Illuminate\Support\Carbon::parse($dates->last_date)->startOfDay();
    $earliest = \Illuminate\Support\Carbon::parse($dates->first_date)->startOfDay();
    $from = $earliest->max($to->copy()->subDays(365));
    $result = app(\App\Services\MeasurementCharts::class)->analyze($actor, $variable, [
        'variable_id' => $variable->id,
        'date_from' => $from->toDateString(),
        'date_to' => $to->toDateString(),
        'group_by' => 'day',
        'chart_type' => 'line',
    ]);
    $data = $result['chartData'];
    $counts = [
        'summary' => $data['summary']['count'],
        'distribution' => array_sum(array_column($data['distribution'], 'count')),
        'trend' => array_sum(array_column($data['trend'], 'count')),
        'stations' => array_sum(array_column($data['stations'], 'count')),
    ];
    if (count(array_unique($counts)) !== 1 || $counts['summary'] < 1) {
        throw new \RuntimeException('Chart counts disagree');
    }

    // Derived SELECT rows exercise MySQL's numeric bindings without storing fixtures.
    $numericProbes = [];
    foreach ([
        'negative_and_boundaries' => array_map(fn ($value) => (string) $value, range(-10, 10, 2)),
        'decimal_extremes' => ['-99999999999.9999', '-99999999999.9998', '0', '99999999999.9998', '99999999999.9999'],
        'tiny_decimals' => ['-0.0002', '-0.0001', '0', '0.0001', '0.0001', '0.0002'],
    ] as $label => $values) {
        $union = null;
        foreach ($values as $value) {
            $row = $db->query()->selectRaw('CAST(? AS DECIMAL(15,4)) AS value_numeric', [$value]);
            $union = $union === null ? $row : $union->unionAll($row);
        }
        $query = $db->query()->fromSub($union, 'probe_values');
        $stats = (clone $query)->selectRaw('COUNT(*) as count, MIN(value_numeric) as minimum, MAX(value_numeric) as maximum')->first();
        $method = new \ReflectionMethod(\App\Services\MeasurementCharts::class, 'distribution');
        $bins = $method->invoke(app(\App\Services\MeasurementCharts::class), $query, $stats);
        $total = array_sum(array_column($bins, 'count'));
        if ($total !== count($values) || count($bins) > 10 || count($bins) < 1) {
            throw new \RuntimeException('Histogram precision check failed');
        }
        if ($label === 'negative_and_boundaries' && (count($bins) !== 10 || $bins[9]['count'] !== 2)) {
            throw new \RuntimeException('Histogram boundary check failed');
        }
        $numericProbes[$label] = ['count' => $total, 'bins' => count($bins)];
    }
    echo json_encode([
        'status' => 'passed',
        'database' => $db->getDriverName(),
        'numeric_probes' => $numericProbes,
        'read_only' => true,
        'counts' => $counts,
        'trend_days' => count($data['trend']),
        'histogram_bins' => count($data['distribution']),
    ]).PHP_EOL;
} catch (\Throwable $error) {
    echo json_encode(['status' => 'failed', 'exception' => get_class($error)]).PHP_EOL;
    exit(1);
} finally {
    if ($db->transactionLevel() > 0) {
        $db->rollBack();
    }
}
