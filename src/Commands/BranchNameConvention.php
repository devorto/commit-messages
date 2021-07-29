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
 * Class BranchName
 *
 * @package App\Services
 */
class BranchNameConvention extends Command
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
     * @var CodeOwnersFile
     */
    protected CodeOwnersFile $codeOwnersFile;

    /**
     * BranchName constructor.
     *
     * @param GithubActionConfig $config
     * @param GithubApiCommands $apiCommands
     * @param CodeOwnersFile $codeOwnersFile
     */
    public function __construct(
        GithubActionConfig $config,
        GithubApiCommands $apiCommands,
        CodeOwnersFile $codeOwnersFile
    ) {
        parent::__construct();

        $this->config = $config;
        $this->apiCommands = $apiCommands;
        $this->codeOwnersFile = $codeOwnersFile;
    }

    /**
     * Configures the command.
     */
    protected function configure()
    {
        $this
            ->setName('branch-naming')
            ->setDescription('Branch naming convention.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (empty($_SERVER['branch_regex'])) {
            throw new RuntimeException('Missing "branch_regex" variable in environment.');
        }
        if (empty($_SERVER['decline_message'])) {
            $message = 'Invalid branch name.';
        } else {
            $message = $_SERVER['decline_message'];
        }

        $branch = $this->config->headRef();
        $jsonFile = $this->config->eventPath();
        if (!file_exists($jsonFile)) {
            throw new RuntimeException('Missing pull request event json file.');
        }

        $json = json_decode($jsonFile, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg());
        }

        if (in_array($json['pull_request']['user']['login'], $this->codeOwnersFile->getRepositoryOwners())) {
            // PR done by code owner, so this check is not required.
            return 0;
        }

        if (1 !== preg_match($_SERVER['branch_regex'], $branch)) {
            return $this->fail($output, $message);
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     *
     * @return int
     */
    protected function fail(OutputInterface $output, string $message): int
    {
        $output->writeln($message);

        $this->apiCommands->placeIssueComment($message);
        $this->apiCommands->closePullRequest();

        return 1;
    }
}
