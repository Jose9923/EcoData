<?php
require '/var/www/html/vendor/autoload.php';
$app = require '/var/www/html/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$key = 'deployment_probe_'.bin2hex(random_bytes(12));
$job = \Illuminate\Queue\CallQueuedClosure::create(static function () use ($key): void {
    \Illuminate\Support\Facades\Cache::put($key, 'ok', 120);
});
\Illuminate\Support\Facades\Bus::dispatch($job);
$processed = false;
for ($attempt = 0; $attempt < 15; $attempt++) {
    if (\Illuminate\Support\Facades\Cache::get($key) === 'ok') {
        $processed = true;
        break;
    }
    usleep(500000);
}
\Illuminate\Support\Facades\Cache::forget($key);
echo json_encode(['queue_probe_processed' => $processed]).PHP_EOL;
exit($processed ? 0 : 1);
