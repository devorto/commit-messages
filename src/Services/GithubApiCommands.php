<?php

namespace App\Services;

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
    public function placeIssueComment(string $message)
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
                ],
                CURLOPT_RETURNTRANSFER => true
            ]
        );
        curl_exec($curl);
        curl_close($curl);
    }
}
