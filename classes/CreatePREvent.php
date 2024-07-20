<?php
declare(strict_types=1);

class CreatePREvent implements Event
{
    public string|null $author;
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
            "Create PR %s %s",
            $this->repository,
            $this->pr_title,
        );
    }

    public function message(): string
    {
        $message = "";
        $message .= sprintf("%s が プルリクエストを作成 (%s) \n", $this->author, $this->repository);
        $message .= sprintf("[%s](%s) \n\n", $this->pr_title, $this->pr_url);
        $message .= $this->pr_comment;

        return $message;
    }
}