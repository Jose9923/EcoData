<?php

use App\Support\EnvironmentPreparer;

require dirname(__DIR__).'/vendor/autoload.php';

EnvironmentPreparer::prepare(dirname(__DIR__));

echo 'Entorno preparado; la configuración y la clave existentes se conservaron.'.PHP_EOL;
