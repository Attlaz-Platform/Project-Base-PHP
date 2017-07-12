<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class TaskExecutionRequest
{
    private $task;
    private $executionId;

    public function __construct(Task $task, string $executionId)
    {
        if (\is_null($task)) {
            throw new \InvalidArgumentException('Task cannot be empty');
        }
        if (\is_null($executionId) || empty($executionId)) {
            throw new \InvalidArgumentException('Execution id cannot be empty');
        }

        $this->task = $task;
        $this->executionId = $executionId;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getExecutionId(): string
    {
        return $this->executionId;
    }

    public static function fromArray(array $input): self
    {
        if (!\key_exists('task', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, task is not defined');
        }
        if (!\key_exists('execution_id', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, execution_id is not defined');
        }

        $taskArray = $input['task'];
        $task = Task::fromArray($taskArray);

        $executionId = $input['execution_id'];

        return new self($task, $executionId);
    }
}