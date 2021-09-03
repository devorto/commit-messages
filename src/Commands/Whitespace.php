<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks if commit doesn't only contain whitespace changes.
 */
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
            ->setDescription('Check if commits only contain formatting.');
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
                'git log origin/%s..origin/%s --pretty=format:"%%H"',
                $this->config->baseRef(),
                $this->config->headRef()
            ),
            $commitHashes,
            $return
        );

        if ($return !== 0) {
            $output->writeln('Could not generate history for commit hashes.');

            return 1;
        }

        $exitCode = 0;
        foreach ($commitHashes as $commitHash) {
            exec('git diff ' . $commitHash, $diff, $return1);
            exec('git diff -w ' . $commitHash, $diffWhitespace, $return2);

            if ($return1 !== 0 || $return2 !== 0) {
                $output->writeln('Could not diff for commit hash: ' . $commitHash);

                return 1;
            }

            if ($diff != $diffWhitespace) {
                $exitCode = 1;
                $this->commands->placeCommitComment($commitHash, 'Formatting commits are not allowed.');
            }
        }

        return $exitCode;
    }
}
