<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class TaskExecutionRequest
{
    private $task;
    private $id;
    private $arguments;

    public function __construct(string $task, string $executionId)
    {
        if (\is_null($task)) {
            throw new \InvalidArgumentException('Task cannot be empty');
        }
        if (\is_null($executionId) || empty($executionId)) {
            throw new \InvalidArgumentException('Execution id cannot be empty');
        }

        $this->task = $task;
        $this->id = $executionId;
        $this->arguments = [];
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
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

    public function getId(): string
    {
        return $this->id;
    }

    public static function fromArray(array $input): self
    {
        if (!\key_exists('id', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, id is not defined');
        }

        if (!\key_exists('task', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, task is not defined');
        }
        if (!\key_exists('arguments', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, arguments is not defined');
        }

        $id = $input['id'];
        $task = $input['task'];

        $request = new self($task, $id);

        if (!\key_exists('arguments', $input)) {
            $arguments = $input['arguments'];
            if (\is_array($arguments)) {
                $request->setArguments($arguments);
            }
        }

        return $request;
    }
}