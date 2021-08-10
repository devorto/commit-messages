<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Add a label to pull request.
 */
class Labels extends Command
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
     * @param GithubApiCommands $apiCommands
     * @param GithubActionConfig $config
     */
    public function __construct(GithubApiCommands $apiCommands, GithubActionConfig $config)
    {
        parent::__construct();

        $this->apiCommands = $apiCommands;
        $this->config = $config;
    }

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('labels')
            ->setDescription('Add labels to repo/pr based on branch name.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $labels = [
            'feature' => [
                'name' => 'feature',
                'color' => '0E8A16',
                'description' => 'Improvements or additions.',
                'branch_prefixes' => ['feature', 'styling']
            ],
            'bug' => [
                'name' => 'bug',
                'color' => 'B60205',
                'description' => 'Something is broken.',
                'branch_prefixes' => ['hotfix', 'bugfix']
            ],
            'styling' => [
                'name' => 'styling',
                'color' => 'FBCA04',
                'description' => 'Look and feel changes.',
                'branch_prefixes' => ['styling']
            ],
            'on hold' => [
                'name' => 'on hold',
                'color' => 'FF9900',
                'description' => 'For delayed issues, currently not being worked on.',
                'branch_prefixes' => []
            ],
            'other' => [
                'name' => 'other',
                'color' => '808080',
                'description' => 'The gray area, for other things like questions.',
                'branch_prefixes' => [] // Will be used for non-matching prefixes.
            ]
        ];

        $this->updateRepositoryLabels($labels);

        $branch = explode('/', $this->config->headRef());
        $branch = array_shift($branch);
        $found = [];
        foreach ($labels as $label) {
            if (in_array($branch, $label['branch_prefixes'], true)) {
                $found[] = $label['name'];
            }
        }
        if (empty($found)) {
            $found[] = 'other';
        }

        $this->apiCommands->setPullRequestLabel(...$found);

        return 0;
    }

    /**
     * @param array $labels
     */
    protected function updateRepositoryLabels(array $labels)
    {
        $apiLabels = $this->apiCommands->getLabels();
        foreach ($apiLabels as $apiLabel) {
            if (isset($labels[$apiLabel['name']])) {
                $label = $labels[$apiLabel['name']];
                $label = [
                    $label['name'],
                    $label['color'],
                    $label['description']
                ];
                $apiLabel = [
                    $apiLabel['name'],
                    $apiLabel['color'],
                    $apiLabel['description']
                ];
                if ($label !== $apiLabel) {
                    $this->apiCommands->deleteLabel($apiLabel['name']);
                    $this->apiCommands->createLabel(...$label);
                }
            } else {
                $this->apiCommands->deleteLabel($apiLabel['name']);
            }
        }
    }
}
