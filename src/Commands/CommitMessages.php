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

        $titles = [];

        $exitCode = 0;
        foreach ($commitHashes as $commitHash) {
            $message = shell_exec('git log --format=%B -n 1 ' . $commitHash);
            $output->writeln(['Validating commit message:', $message, '']);

            $messageParts = explode("\n", $message);
            $titles[$commitHash] = empty($messageParts[0]) ? '' : trim($messageParts[0]);

            $tests = [
                'Subject: Max. 50 characters.' => !empty($messageParts[0]) && mb_strlen($messageParts[0]) < 50,
                'Subject: Min. 3 words.' => !empty($messageParts[0]) && count(explode(' ', $messageParts[0])) > 2,
                'Subject: Must not have unnecessary spaces.' => !empty($messageParts[0]) && trim($messageParts[0]) === $messageParts[0],
                'Subject: Must not end with a dot.' => !empty($messageParts[0]) && substr(trim($messageParts[0]), -1) !== '.',
                'Empty line between subject/body.' => preg_match('/.*\n\n.*/', $message) === 1,
                'Subject should not be repeated in body.' => !empty($messageParts[0])
                    && !empty($messageParts[2])
                    && stripos($messageParts[2], trim($messageParts[0], " \t\n\r\0\x0B.")) === false,
                'Body (line 1): Min. 3 words.' => !empty($messageParts[2]) && count(explode(' ', $messageParts[2])) > 2
            ];

            if ($tests['Body (line 1): Min. 3 words.']) {
                $messageParts = array_splice($messageParts, 2);
                $i = 0;
                foreach ($messageParts as $part) {
                    $tests['Body (line ' . ++$i . '): Max. 72 characters.'] = mb_strlen($part) < 73;
                    $tests['Body (line ' . $i . '): Must not have unnecessary spaces.'] = trim($part) === $part;
                }
            } else {
                $tests['Body (line 1): Max. 72 characters.'] = false;
                $tests['Body (line 1): Must not have unnecessary spaces.'] = false;
            }

            if (in_array(false, $tests, true)) {
                $this->placeCommitComment($commitHash, $tests);
                $exitCode = 1;
            }
        }

        $duplicates = $this->duplicateTitles($titles);
        if (!empty($duplicates)) {
            $this->placeComment($duplicates);
            $exitCode = 1;
        }

        return $exitCode;
    }

    /**
     * @param string $commitHash
     * @param array $tests
     */
    protected function placeCommitComment(string $commitHash, array $tests): void
    {
        // Is there already a comment on this commit?
        if (!empty($this->apiCommands->getCommitComments($commitHash))) {
            return;
        }

        $message = <<<MESSAGE
Invalid commit message, structure should be:
```
Subject (Min. 3 words. Max. 50 characters).
-- blank line --
Long description, can be multiple lines (Min. 3 words, Max. 72 characters per line).
```

MESSAGE;
        foreach ($tests as $test => $result) {
            $message .= sprintf("- [%s] %s\n", $result ? 'x' : ' ', $test);
        }

        $this->apiCommands->placeCommitComment($commitHash, $message);
    }

    /**
     * Add comment to github.
     */
    protected function placeComment(array $duplicates): void
    {
        $message = <<<MESSAGE
Repetitive commits found, make sure commits are unique or combined:

MESSAGE;
        foreach ($duplicates as $title => $hashes) {
            $message .= $title . ' (' . implode(', ', $hashes) . ')' . PHP_EOL;
        }

        $this->apiCommands->placeIssueComment($message);
    }

    /**
     * @param array $titles
     *
     * @return array
     */
    protected function duplicateTitles(array $titles): array
    {
        $duplicates = [];

        $titles = array_map(
            function (string $title): string {
                // Strip titles a little for this check.
                return trim($title, " \t\n\r\0\x0B.");
            },
            $titles
        );

        foreach ($titles as $hash => $title) {
            // Copy all titles.
            $copy = $titles;

            // Unset our hash, so we won't find ourselves.
            unset($copy[$hash]);

            // Find duplicate.
            if (in_array($title, $copy, true)) {
                $duplicates[$title][] = $hash;
            }
        }

        // Make sure sub-arrays only have non-duplicate hashes.
        return array_map('array_unique', $duplicates);
    }
}
