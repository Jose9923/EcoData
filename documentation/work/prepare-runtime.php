<?php
foreach (['storage', 'bootstrap/cache'] as $dir) {
    $base = '/var/www/html/'.$dir;
    $paths = [$base];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $entry) {
        if (!$entry->isLink()) {
            $paths[] = $entry->getPathname();
        }
    }
    foreach ($paths as $path) {
        if (!chown($path, 1000) || !chgrp($path, 33) || !chmod($path, is_dir($path) ? 02775 : 0664)) {
            exit(1);
        }
    }
}
$env = '/var/www/html/.env';
if (!chown($env, 1000) || !chgrp($env, 33) || !chmod($env, 0640)) {
    exit(1);
}
echo "Runtime permissions prepared.\n";
