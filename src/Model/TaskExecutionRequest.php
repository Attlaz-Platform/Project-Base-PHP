<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class TaskExecutionRequest
{
    private $task;
    private $id;

    public function __construct(Task $task, string $executionId)
    {
        if (\is_null($task)) {
            throw new \InvalidArgumentException('Task cannot be empty');
        }
        if (\is_null($executionId) || empty($executionId)) {
            throw new \InvalidArgumentException('Execution id cannot be empty');
        }

        $this->task = $task;
        $this->id = $executionId;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public static function fromArray(array $input): self
    {
        if (!\key_exists('task', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, task is not defined');
        }
        if (!\key_exists('id', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, id is not defined');
        }

        $taskArray = $input['task'];
        $task = Task::fromArray($taskArray);

        $id = $input['id'];

        return new self($task, $id);
    }
}