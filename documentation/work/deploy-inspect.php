<?php
require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = \Illuminate\Support\Facades\DB::connection();
$schema = \Illuminate\Support\Facades\Schema::getTables();
$counts = [];
foreach (['users','schools','physical_variable_records','physical_variable_record_values','field_diary_activities','field_diary_submissions','field_diary_answers','jobs','failed_jobs'] as $table) {
    $counts[$table] = \Illuminate\Support\Facades\Schema::hasTable($table) ? $db->table($table)->count() : null;
}
echo json_encode([
    'app_env'=>config('app.env'), 'app_url'=>config('app.url'),
    'app_key_present'=>filled(config('app.key')),
    'app_key_fingerprint'=>hash('sha256', (string)config('app.key')),
    'timezone'=>config('app.timezone'), 'db_driver'=>$db->getDriverName(),
    'db_host'=>config('database.connections.'.config('database.default').'.host'),
    'db_name'=>$db->getDatabaseName(), 'queue'=>config('queue.default'),
    'retry_after'=>config('queue.connections.'.config('queue.default').'.retry_after'),
    'cache'=>config('cache.default'), 'mail'=>config('mail.default'),
    'migration_applied'=>$db->table('migrations')->where('migration','2026_10_26_120000_add_measurement_audits_and_import_receipts')->exists(),
    'audit_table'=>\Illuminate\Support\Facades\Schema::hasTable('physical_variable_record_audits'),
    'receipts_table'=>\Illuminate\Support\Facades\Schema::hasTable('measurement_import_receipts'),
    'counts'=>$counts,
], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
