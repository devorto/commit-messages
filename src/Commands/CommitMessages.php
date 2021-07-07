<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
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
     * CommitMessages constructor.
     *
     * @param GithubActionConfig $config
     */
    public function __construct(GithubActionConfig $config)
    {
        parent::__construct();

        $this->config = $config;
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
            echo 'Could not generated history for commit hashes.';
            exit(1);
        }

        $exitCode = 0;
        foreach ($commitHashes as $commitHash) {
            $message = shell_exec('git log --format=%B -n 1 ' . $commitHash);
            echo "Validating commit message:\n$message\n\n";
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
            $this->placeComment($message);

            $exitCode = 1;
        }

        return $exitCode;
    }

    /**
     * @param string $message
     */
    protected function placeComment(string $message)
    {
        $curl = curl_init(sprintf(
            '%s/repos/%s/issues/%s/comments',
            $this->config->apiUrl(),
            $this->config->repository(),
            $this->config->pullRequestNumber()
        ));
        curl_setopt_array(
            $curl,
            [
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(['body' => $message]),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/vnd.github.v3+json',
                    'Content-Type: application/json',
                    'Authorization: Token ' . $this->config->token(),
                    'User-Agent: ' . $this->config->actor()
                ],
                CURLOPT_RETURNTRANSFER => true
            ]
        );
        curl_exec($curl);
        curl_close($curl);
    }
}
