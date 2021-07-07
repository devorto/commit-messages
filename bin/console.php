<?php

use App\Commands\CommitMessages;
use App\Services\GithubActionConfig;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application('Devorto Commit Messages', '1.0.0');
$app->add(new CommitMessages(new GithubActionConfig()));
$app->run();
