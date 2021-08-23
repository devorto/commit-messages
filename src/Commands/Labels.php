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

        $new = array_keys($labels);
        $delete = array_map(
            function (array $data): string {
                return $data['name'];
            },
            $apiLabels
        );
        $updates = array_intersect($new, $delete);

        foreach ($updates as $update) {
            unset($new[array_search($update, $new)]);
            unset($delete[array_search($update, $delete)]);

            foreach ($apiLabels as $apiLabel) {
                if ($apiLabel['name'] === $update) {
                    if (
                        $apiLabel['color'] !== $labels[$update]['color']
                        || $apiLabel['description'] !== $labels[$update]['description']
                    ) {
                        $this->apiCommands->deleteLabel($update);
                        $this->apiCommands->createLabel(
                            $update,
                            $labels[$update]['color'],
                            $labels[$update]['description']
                        );
                    }
                    break;
                }
            }
        }

        foreach ($new as $key) {
            $this->apiCommands->createLabel($key, $labels[$key]['color'], $labels[$key]['description']);
        }

        foreach ($delete as $key) {
            $this->apiCommands->deleteLabel($key);
        }
    }
}
