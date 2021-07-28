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

            if (preg_match("/.{0,50}\n\n.+/", $message) !== 1) {
                $this->placeComment();
                break;
            } elseif (!$this->messageLengthValid($message)) {
                $this->placeComment();
                break;
            }

            $exitCode = 1;
        }

        return $exitCode;
    }

    /**
     * Add comment to github.
     */
    protected function placeComment(): void
    {
        $message = <<<MESSAGE
Pull request has one or more commits with invalid format.
The format of a commit should be:
```
Subject (Max 50 characters)
-- blank line --
Long description (Max 72 characters)
```
MESSAGE;
        $this->apiCommands->placeIssueComment($message);
    }

    /**
     * Validate commit message length.
     *
     * @param string $message
     *
     * @return bool
     */
    protected function messageLengthValid(string $message): bool
    {
        $message = explode("\n", $message);
        // Remove title and empty line.
        array_splice($message, 2);
        foreach ($message as $line) {
            if (mb_strlen($line) > 72) {
                return false;
            }
        }

        return true;
    }
}
