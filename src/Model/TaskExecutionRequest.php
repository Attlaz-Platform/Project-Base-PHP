<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class TaskExecutionRequest
{
    private $task;
    private $command;
    private $arguments;
    private $executionId;

    public function __construct(string $task, string $command, array $arguments = [], string $executionId = null)
    {
        if (\is_null($task)) {
            throw new \InvalidArgumentException('Task cannot be empty');
        }
        if (\is_null($command) || empty($command)) {
            throw new \InvalidArgumentException('Command cannot be empty');
        }

        $this->task = $task;
        $this->command = $command;
        $this->arguments = $arguments;
        $this->executionId = $executionId;
    }

    public function getTask(): string
    {
        return $this->task;
    }

    public function getCommand(): string
    {
        return $this->command;
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

//    public static function fromArray(array $input): self
//    {
//        if (!\key_exists('id', $input)) {
//            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, id is not defined');
//        }
//
//        if (!\key_exists('task', $input)) {
//            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, task is not defined');
//        }
//        if (!\key_exists('arguments', $input)) {
//            throw new \InvalidArgumentException('Unable to deserialize TaskExecutionRequest, arguments is not defined');
//        }
//
//        $id = $input['id'];
//        $task = $input['task'];
//
//        $request = new self($task, $id);
//
//        if (\key_exists('arguments', $input)) {
//            $arguments = $input['arguments'];
//            if (\is_array($arguments)) {
//                $request->setArguments($arguments);
//            }
//        }
//
//        return $request;
//    }
}