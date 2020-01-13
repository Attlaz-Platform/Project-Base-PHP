<?php

declare(strict_types=1);

namespace Attlaz\Project\Model;

class TaskExecutionRequest
{
    private $task;

    private $arguments;
    private $executionId;

    public function __construct(string $task, array $arguments = [], string $executionId = null)
    {
        if (empty($task)) {
            throw new \InvalidArgumentException('Task cannot be empty');
        }

        $this->task = $task;
        $this->arguments = $arguments;
        $this->executionId = $executionId;
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    public function getArgument(string $name)
    {
        return $this->arguments[$name];
    }

    public function getExecutionId(): string
    {
        return $this->executionId;
    }
}
