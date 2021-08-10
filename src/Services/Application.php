<?php

namespace App\Services;

use Devorto\DependencyInjection\DependencyInjection;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Command\Command;

/**
 * Autoloading of symfony commands.
 */
class Application extends SymfonyConsole
{
    /**
     * Add a directory with commands.
     *
     * @param string $directory
     * @param string $namespacePrefix
     *
     * @return Application
     */
    public function addDirectory(string $directory, string $namespacePrefix): Application
    {
        $path = realpath($directory);
        if (false === $path) {
            throw new InvalidArgumentException("Directory '$directory' not found.");
        }

        $files = scandir($path);
        foreach ($files as $file) {
            // Hidden files or '..' so skip.
            if (substr($file, 0, 1) === '.') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->addDirectory($path, $namespacePrefix . '\\' . $file);
                continue;
            }

            if (substr($file, -4) !== '.php') {
                continue;
            }

            $file = substr($file, 0, -4);
            $file = $namespacePrefix . '\\' . $file;

            try {
                $class = new ReflectionClass($file);
                if ($class->isInterface() || $class->isAbstract()) {
                    continue;
                }
            } catch (ReflectionException $exception) {
                throw new RuntimeException("Cannot load class '$file' based on file name.", 0, $exception);
            }

            /** @var Command $command */
            $command = DependencyInjection::instantiate($file);
            $this->add($command);
        }

        return $this;
    }
}
