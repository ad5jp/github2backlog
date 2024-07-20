<?php
declare(strict_types=1);

class ClosePREvent implements Event
{
    public string|null $author;
    public bool $is_merged;
    public string|null $pr_title;
    public string|null $pr_comment;
    public string|null $pr_url;
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

            if ($this->pr_title) {
                preg_match_all($pattern, $this->pr_title, $matches);
                foreach ($matches as $match) {
                    $issue_keys[] = $match[0];
                }
            }

            if ($this->pr_comment) {
                preg_match_all($pattern, $this->pr_comment, $matches);
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
            "%s PR %s %s",
            ($this->is_merged ? 'Merge' : 'Close'),
            $this->repository,
            $this->pr_title,
        );
    }

    public function message(): string
    {
        $action = ($this->is_merged ? 'マージ' : 'クローズ');

        $message = "";
        $message .= sprintf("### %s が プルリクエストを%s (%s) \n\n", $this->author, $action, $this->repository);
        $message .= sprintf("[%s](%s)", $this->pr_title, $this->pr_url);

        return $message;
    }
}