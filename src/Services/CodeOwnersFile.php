<?php

namespace App\Services;

/**
 * Class CodeOwnersFile
 *
 * @package App\Services
 */
class CodeOwnersFile
{
    /**
     * @return array
     */
    public function getRepositoryOwners(): array
    {
        static $owners = null;
        if ($owners !== null) {
            return $owners;
        }

        $path = getcwd() . '/CODEOWNERS';
        if (file_exists($path)) {
            $content = file($path);
        } else {
            $path = getcwd() . '/.github/CODEOWNERS';
            if (file_exists($path)) {
                $content = file($path);
            }
        }
        unset($path);

        $owners = [];
        if (empty($content)) {
            return $owners;
        }

        foreach ($content as $owner) {
            $owner = explode(' ', $owner);

            // Currently only support full repository owners.
            if (substr($owner, 0, 1) !== '*') {
                continue;
            }

            // Multiple owners per line possible, so remove path and merge the owners.
            array_shift($owner);
            $owner = array_map(
                function (string $owner): string {
                    return trim($owner, " \t\n\r\0\x0B@");
                },
                $owner
            );
            $owner = array_filter($owner);
            $owners = array_merge($owners, $owner);
        }

        return $owners;
    }
}
