<?php

use App\Commands\Assignee;
use App\Commands\BranchNameConvention;
use App\Commands\CodeOwner;
use App\Commands\CommitMessages;
use App\Commands\Labels;
use App\Services\CodeOwnersFile;
use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Application('Devorto Commit Messages', '1.0.0');

$config = new GithubActionConfig();
$commands = new GithubApiCommands($config);
$owner = new CodeOwnersFile();

$app->add(new Assignee($config, $commands));
$app->add(new BranchNameConvention($config, $commands, $owner));
$app->add(new CommitMessages($config, $commands));
$app->add(new Labels($commands, $config));
$app->add(new CodeOwner($commands, $config, $owner));
$app->run();
