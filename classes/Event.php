<?php
declare(strict_types=1);

interface Event
{
    /**
     * @param string[] $project_keys
     * @return string[]
     */
    public function getIssueKeys(array $project_keys): array;

    public function summary(): string;

    public function message(): string;
}