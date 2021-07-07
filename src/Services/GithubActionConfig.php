<?php

namespace App\Services;

/**
 * Class GithubActionConfig
 *
 * @see https://docs.github.com/en/actions/reference/environment-variables
 */
class GithubActionConfig
{
    /**
     * The GitHub workspace directory path.
     * The workspace directory is a copy of your repository if your workflow uses the actions/checkout action.
     * If you don't use the actions/checkout action, the directory will be empty.
     * For example, /home/runner/work/my-repo-name/my-repo-name.
     *
     * @return string
     */
    public function workspace(): string
    {
        return $_SERVER['GITHUB_WORKSPACE'];
    }

    /**
     * The unique identifier (id) of the action.
     *
     * @return string
     */
    public function action(): string
    {
        return $_SERVER['GITHUB_ACTION'];
    }

    /**
     * A unique number for each run of a particular workflow in a repository.
     * This number begins at 1 for the workflow's first run, and increments with each new run.
     * This number does not change if you re-run the workflow run.
     *
     * @return int
     */
    public function runNumber(): int
    {
        return $_SERVER['GITHUB_RUN_NUMBER'];
    }

    /**
     * The commit SHA that triggered the workflow. For example, ffac537e6cbbf934b08745a378932722df287a53.
     *
     * @return string
     */
    public function sha(): string
    {
        return $_SERVER['GITHUB_SHA'];
    }

    /**
     * Custom function, extracts pull request number from $this->ref().
     *
     * @return null|int
     * @see ref
     */
    public function pullRequestNumber(): ?int
    {
        if (empty($this->ref())) {
            return null;
        }

        return (int)str_replace(['refs/pull/', '/merge'], '', $this->ref());
    }

    /**
     * The branch or tag ref that triggered the workflow.
     * For example, refs/heads/feature-branch-1.
     * If neither a branch or tag is available for the event type, the variable will not exist.
     *
     * @return null|string
     */
    public function ref(): ?string
    {
        return $_SERVER['GITHUB_REF'] ?? null;
    }

    /**
     * Returns the API URL. For example: https://api.github.com.
     *
     * @return string
     */
    public function apiUrl(): string
    {
        return $_SERVER['GITHUB_API_URL'];
    }

    /**
     * The path of the file with the complete webhook event payload. For example, /github/workflow/event.json.
     *
     * @return string
     */
    public function eventPath(): string
    {
        return $_SERVER['GITHUB_EVENT_PATH'];
    }

    /**
     * The name of the webhook event that triggered the workflow.
     *
     * @return string
     */
    public function eventName(): string
    {
        return $_SERVER['GITHUB_EVENT_NAME'];
    }

    /**
     * A unique number for each run within a repository. This number does not change if you re-run the workflow run.
     *
     * @return int
     */
    public function runId(): int
    {
        return $_SERVER['GITHUB_RUN_ID'];
    }

    /**
     * The name of the person or app that initiated the workflow. For example, octocat.
     *
     * @return string
     */
    public function actor(): string
    {
        return $_SERVER['GITHUB_ACTOR'];
    }

    /**
     * Returns the GraphQL API URL. For example: https://api.github.com/graphql.
     *
     * @return string
     */
    public function graphqlUrl(): string
    {
        return $_SERVER['GITHUB_GRAPHQL_URL'];
    }

    /**
     * Returns the URL of the GitHub server. For example: https://github.com.
     *
     * @return string
     */
    public function serverUrl(): string
    {
        return $_SERVER['GITHUB_SERVER_URL'];
    }

    /**
     * The job_id of the current job.
     *
     * @return string
     */
    public function job(): string
    {
        return $_SERVER['GITHUB_JOB'];
    }

    /**
     * The owner and repository name. For example, octocat/Hello-World.
     *
     * @return string
     */
    public function repository(): string
    {
        return $_SERVER['GITHUB_REPOSITORY'];
    }

    /**
     * Only set for pull request events. The name of the base branch.
     *
     * @return null|string
     */
    public function baseRef(): ?string
    {
        return $_SERVER['GITHUB_BASE_REF'] ?? null;
    }

    /**
     * Only set for pull request events. The name of the head branch.
     *
     * @return null|string
     */
    public function headRef(): ?string
    {
        return $_SERVER['GITHUB_HEAD_REF'] ?? null;
    }

    /**
     * The name of the workflow.
     *
     * @return string
     */
    public function workflow(): string
    {
        return $_SERVER['GITHUB_WORKFLOW'];
    }

    /**
     * Note: normally in a github action this environment variable does not exists like this.
     * To make sure this works, use the env field in your action with: GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
     * this way it's available for everyone using this object.
     *
     * @return string|null
     */
    public function token(): ?string
    {
        return $_SERVER['GITHUB_TOKEN'] ?? null;
    }
}
