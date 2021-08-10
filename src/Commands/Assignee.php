<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Assign pull request owner to pull request if no assignee is selected.
 */
class Assignee extends Command
{
    /**
     * @var GithubActionConfig
     */
    protected GithubActionConfig $config;

    /**
     * @var GithubApiCommands
     */
    protected GithubApiCommands $commands;

    /**
     * @param GithubActionConfig $config
     * @param GithubApiCommands $commands
     */
    public function __construct(GithubActionConfig $config, GithubApiCommands $commands)
    {
        parent::__construct();

        $this->config = $config;
        $this->commands = $commands;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('assignee')
            ->setDescription('Assign PR owner to PR, if no assignee.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jsonFile = $this->config->eventPath();
        if (!file_exists($jsonFile)) {
            throw new RuntimeException('Missing pull request event json file.');
        }

        $json = json_decode(file_get_contents($jsonFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg());
        }

        if (empty($json['pull_request']['assignees'])) {
            $this->commands->setAssignee($json['pull_request']['user']['login']);
        }

        return 0;
    }
}
