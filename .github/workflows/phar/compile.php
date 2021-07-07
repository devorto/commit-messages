<?php

$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'result.phar';

if (file_exists($file)) {
    unlink($file);
}

$phar = new Phar($file);
$phar->buildFromDirectory(__DIR__ . '/../../../');
$phar->setDefaultStub('bin/console.php');
$phar->setStub("#!/usr/bin/env php\n" . $phar->getStub());
$phar->compressFiles(Phar::GZ);

echo 'File compiled: ' . filesize($file) . ' bytes written.' . PHP_EOL;
