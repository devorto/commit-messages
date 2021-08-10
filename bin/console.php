<?php

use App\Services\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application('Devorto Commit Messages', '1.0.0');
$app->addDirectory(__DIR__ . '/../src/Commands', 'App\Commands');
$app->run();
