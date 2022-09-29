<?php

namespace App\Commands;

use App\Services\GithubActionConfig;
use App\Services\GithubApiCommands;
use RuntimeException;
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
            $output->writeln('PHPCS not found.');

            return 1;
        }

        exec($path . ' --report-json', $json, $code);
        if ($code !== 0 && empty($json)) {
            $output->writeln('No json generated.');

            return 1;
        }

        $json = json_decode(implode('', $json), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $output->writeln('Json Error: ' . json_last_error_msg());

            return 1;
        }

        $diff = $this->getDiff();

        $comments = [];
        $body = '';
        foreach ($json['files'] as $file => $results) {
            if (empty($results['messages'])) {
                continue;
            }

            $file = ltrim(str_replace(getcwd(), '', $file), '/');

            foreach ($results['messages'] as $message) {
                $output->writeln('File: ' . $file);
                $output->writeln('Line: ' . $message['line']);
                $output->writeln('Message: ' . $message['message']);
                $output->writeln('');

                if (isset($diff[$file][$message['line']])) {
                    $comments[] = [
                        'path' => $file,
                        'position' => $diff[$file][$message['line']],
                        'body' => $message['message']
                    ];
                } else {
                    if (empty($body)) {
                        $body = "Problematic files found outside Pull Request diff:\n\n";
                    }

                    $body .= sprintf(
                        "%s in [%s on line %s](%s)\n\n",
                        $message['message'],
                        $file,
                        $message['line'],
                        sprintf(
                            'https://github.com/%s/blob/%s/%s#L%s',
                            $this->config->repository(),
                            $this->config->headRef(),
                            $file,
                            $message['line']
                        )
                    );
                }
            }
        }

        if (!empty($comments) || !empty($body)) {
            $this->apiCommands->addPullRequestReview(
                "CodeSniffer found some issues, please check your code.\n\n$body",
                'COMMENT',
                $comments
            );

            return 1;
        }

        return 0;
    }

    /**
     * @return array
     */
    protected function getDiff(): array
    {
        $output = $this->apiCommands->getPullRequestDiff();
        $output = explode("\n", $output);

        $files = [];
        $lastFile = '';
        foreach ($output as $line) {
            if (strpos($line, 'diff --git') !== false) {
                $lastFile = explode(' b/', $line);
                $lastFile = array_pop($lastFile);
                continue;
            }

            $files[$lastFile][] = $line;
        }

        // Remove deleted and renamed only files from diff.
        $files = array_filter($files, function (array $diff) {
            if (!empty(preg_grep('/^binary files (.+) and (.+) differ$/i', $diff))) {
                return false;
            }

            return stripos($diff[0], 'deleted file') === false && stripos($diff[0], 'similarity index 100%') === false;
        });

        return array_map(
            function (array $diff): array {
                $results = [];

                $index = preg_grep('/^@@[-+,0-9 ]+@@/', $diff);
                if (empty($index)) {
                    return $results;
                }

                for ($i = 0; $i < key($index); $i++) {
                    unset($diff[$i]);
                }

                $position = 0;
                $currentLine = 0;
                foreach ($diff as $line) {
                    if (1 === preg_match('/^@@ [^\s]+ \+([0-9]+)(,[0-9]+)? @@/', $line, $matches)) {
                        $currentLine = (int)$matches[1];
                        $position++;
                        continue;
                    }

                    switch (substr($line, 0, 1)) {
                        case ' ':
                        case '+':
                            $results[$currentLine] = $position;
                            $currentLine++;
                            $position++;
                            break;
                        case '-':
                            $position++;
                            break;
                        case '':
                        case '\\':
                            // Do nothing.
                            break;
                        default:
                            throw new RuntimeException('Could not parse git diff, unknown character: ' . substr($line,
                                    0, 1));
                    }
                }

                if (empty($results)) {
                    throw new RuntimeException('Diff is empty?');
                }

                return $results;
            },
            $files
        );
    }
}
