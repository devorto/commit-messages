<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
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
     * BranchName constructor.
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
        $branch = $this->config->headRef();
        $parts = explode('/', $branch);
        if (count($parts) !== 2) {
            return $this->fail($output);
        }

        if (!in_array($parts[0], ['feature', 'bugfix', 'styling'])) {
            return $this->fail($output);
        }

        if (1 !== preg_match('/^[a-z]+\/[0-9]+-[a-z0-9-]+$/', $branch)) {
            return $this->fail($output);
        }

        return 0;
    }

    /**
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function fail(OutputInterface $output): int
    {
        $message = <<<TXT
Invalid branch name.

Format should be: type/issue(or-ticket-number)-short-description
Type is one of the following: feature/bugfix/styling.
Short description should be lowercase a-z, 0-9 and - only.
TXT;
        $output->writeln($message);

        $this->apiCommands->placeIssueComment($message);
        $this->apiCommands->closePullRequest();

        return 1;
    }
}
