<?php

namespace App\Commands;

use App\Services\CodeOwnersFile;
use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Approve pull request made by code owner.
 */
class CodeOwner extends Command
{
    /**
     * @var GithubApiCommands
     */
    protected GithubApiCommands $apiCommands;

    /**
     * @var GithubActionConfig
     */
    protected GithubActionConfig $config;

    /**
     * @var CodeOwnersFile
     */
    protected CodeOwnersFile $codeOwnersFile;

    /**
     * @param GithubApiCommands $apiCommands
     * @param GithubActionConfig $config
     * @param CodeOwnersFile $codeOwnersFile
     */
    public function __construct(
        GithubApiCommands $apiCommands,
        GithubActionConfig $config,
        CodeOwnersFile $codeOwnersFile
    ) {
        parent::__construct();

        $this->apiCommands = $apiCommands;
        $this->config = $config;
        $this->codeOwnersFile = $codeOwnersFile;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('code-owner')
            ->setDescription('Automatically approve pull requests made by code owner.');
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

        if (!in_array($json['pull_request']['user']['login'], $this->codeOwnersFile->getRepositoryOwners())) {
            // PR is not done by code owner, so exit.
            return 0;
        }

        $this->apiCommands->addPullRequestReview(
            'Pull request automatically approved, pull request is done by a code owner.',
            'APPROVE'
        );

        return 0;
    }
}
