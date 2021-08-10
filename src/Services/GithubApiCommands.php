<?php

namespace App\Services;

use RuntimeException;

/**
 * Class GithubApiCommands
 *
 * @package App\Services
 */
class GithubApiCommands
{
    /**
     * @var GithubActionConfig
     */
    protected GithubActionConfig $config;

    /**
     * GithubApiCommands constructor.
     *
     * @param GithubActionConfig $config
     */
    public function __construct(GithubActionConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $message
     */
    public function placeIssueComment(string $message): void
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
                ]
            ]
        );
        curl_exec($curl);
        curl_close($curl);
    }

    /**
     * @param string $commitHash
     *
     * @return array
     */
    public function getCommitComments(string $commitHash): array
    {
        $curl = curl_init(sprintf(
            '%s/repos/%s/commits/%s/comments',
            $this->config->apiUrl(),
            $this->config->repository(),
            $commitHash
        ));
        curl_setopt_array(
            $curl,
            [
                CURLOPT_HTTPHEADER => [
                    'Accept: application/vnd.github.v3+json',
                    'Authorization: Token ' . $this->config->token(),
                    'User-Agent: ' . $this->config->actor()
                ],
                CURLOPT_RETURNTRANSFER => true
            ]
        );
        $data = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($status !== 200) {
            throw new RuntimeException(sprintf('Github endpoint error (%s): %s', $status, $data));
        }

        if (empty($data)) {
            return [];
        }

        $data = json_decode($data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg());
        }

        return $data;
    }

    /**
     * @param string $commitHash
     * @param string $comment
     */
    public function placeCommitComment(string $commitHash, string $comment): void
    {
        $curl = curl_init(sprintf(
            '%s/repos/%s/commits/%s/comments',
            $this->config->apiUrl(),
            $this->config->repository(),
            $commitHash
        ));
        curl_setopt_array(
            $curl,
            [
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(['body' => $comment, 'position' => 0]),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/vnd.github.v3+json',
                    'Content-Type: application/json',
                    'Authorization: Token ' . $this->config->token(),
                    'User-Agent: ' . $this->config->actor()
                ]
            ]
        );
        curl_exec($curl);
        curl_close($curl);
    }

    public function closePullRequest(): void
    {
        $curl = curl_init(sprintf(
            '%s/repos/%s/pulls/%s',
            $this->config->apiUrl(),
            $this->config->repository(),
            $this->config->pullRequestNumber()
        ));
        curl_setopt_array(
            $curl,
            [
                CURLOPT_CUSTOMREQUEST => 'PATCH',
                CURLOPT_POSTFIELDS => json_encode(['state' => 'closed']),
                CURLOPT_HTTPHEADER => [
                    'Accept: application/vnd.github.v3+json',
                    'Content-Type: application/json',
                    'Authorization: Token ' . $this->config->token(),
                    'User-Agent: ' . $this->config->actor()
                ]
            ]
        );
        curl_exec($curl);
        curl_close($curl);
    }
}
