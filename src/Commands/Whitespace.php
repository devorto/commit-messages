<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Whitespace extends Command
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
            ->setName('whitespace')
            ->setDescription('Check if commits are only formatting');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        exec(
            sprintf(
                'git diff origin/%s..origin/%s',
                $this->config->baseRef(),
                $this->config->headRef()
            ),
            $nonWhiteSpace,
            $return
        );

        if ($return !== 0) {
            $output->writeln('Could not generate diff for commits.');

            return 1;
        }

        exec(
            sprintf(
                'git diff -w origin/%s..origin/%s',
                $this->config->baseRef(),
                $this->config->headRef()
            ),
            $whiteSpaced,
            $return
        );

        if ($return !== 0) {
            $output->writeln('Could not generate diff for commits.');

            return 1;
        }

        if ($nonWhiteSpace !== $whiteSpaced) {
            $output->writeln('Commits with only formatting detected.');

            return 1;
        }

        return 0;
    }
}
