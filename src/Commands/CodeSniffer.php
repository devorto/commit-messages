<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CodeSniffer
 *
 * @package App\Commands
 */
class CodeSniffer extends Command
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
            ->setName('codesniffer')
            ->setDescription('Validate code using phpcs.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = getcwd() . '/vendor/bin/phpcs';
        if (!file_exists($path)) {
            return 1;
        }

        exec($path . ' --report-json', $json, $code);
        if ($code !== 0 && empty($json)) {
            $output->writeln('No json generated');
            return 1;
        }

        $json = json_decode(implode('', $json), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $output->writeln('Json Error:' . json_last_error_msg());
            return 1;
        }

        $code = 0;
        foreach ($json['files'] as $file => $results) {
            if (empty($results['messages'])) {
                continue;
            }

            $code = 1;
            $file = ltrim(str_replace(getcwd(), '', $file), '/');

            foreach ($results['messages'] as $message) {
                $this->apiCommands->placeCommitComment(
                    $this->config->sha(),
                    $message['message'],
                    $file,
                    $message['line']
                );
                $output->writeln('File: ' . $file);
                $output->writeln('Line: ' . $message['line']);
                $output->writeln('Message: ' . $message['message']);
            }
        }

        return $code;
    }
}
