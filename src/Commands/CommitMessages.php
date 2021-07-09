<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CommitMessages
 *
 * @package App\Commands
 */
class CommitMessages extends Command
{
    /**
     * @var GithubActionConfig
     */
    protected GithubActionConfig $config;

    /**
     * @var GithubApiCommands
     */
    protected GithubApiCommands $apiCommands;

    /**
     * CommitMessages constructor.
     *
     * @param GithubActionConfig $config
     * @param GithubApiCommands $apiCommands
     */
    public function __construct(GithubActionConfig $config, GithubApiCommands $apiCommands)
    {
        parent::__construct();

        $this->config = $config;
        $this->apiCommands = $apiCommands;
    }

    /**
     * Configures the command.
     */
    protected function configure()
    {
        $this
            ->setName('commit-messages')
            ->setDescription('Validate commit messages of git repository.');
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
            $message = shell_exec('git log --format=%B -n 1 ' . $commitHash);
            $output->writeln(["Validating commit message:", $message, '']);
            if (preg_match("/.{0,50}\n\n.+/", $message) === 1) {
                continue;
            }

            $message = <<<MESSAGE
Commit $commitHash, with message:
```
$message
```
Has an invalid format, it should be:
```
Subject (Max 50 characters)

Long description
```
MESSAGE;
            $this->apiCommands->placeIssueComment($message);

            $exitCode = 1;
        }

        return $exitCode;
    }
}
