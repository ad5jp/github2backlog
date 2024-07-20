<?php
declare(strict_types=1);

class CommitEvent implements Event
{
    public string|null $author;
    public string|null $commit_id;
    public string|null $commit_message;
    public string|null $commit_url;
    public string|null $branch;
    public string|null $repository;

    /**
     * @param string[] $project_keys
     * @return string[]
     */
    public function getIssueKeys(array $project_keys): array
    {
        $issue_keys = [];

        foreach ($project_keys as $project_key) {
            $pattern = sprintf("/%s\-[0-9]+/", $project_key);

            if ($this->commit_message) {
                preg_match_all($pattern, $this->commit_message, $matches);
                foreach ($matches as $match) {
                    $issue_keys[] = $match[0];
                }
            }
        }

        return array_values(array_unique($issue_keys));
    }

    public function summary(): string
    {
        return sprintf(
            "Commit %s %s %s",
            $this->repository,
            $this->branch,
            $this->commit_id,
        );
    }

    public function message(): string
    {
        $message = "";
        $message .= sprintf("%s が %s にコミット (%s) \n", $this->author, $this->branch, $this->repository);
        $message .= sprintf("[%s](%s) \n\n", $this->commit_id, $this->commit_url);
        $message .= $this->commit_message;

        return $message;
    }
}