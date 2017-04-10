<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

class TaskResult implements \JsonSerializable
{
    private $task;
    private $data;
    private $success;

    public function __construct(Task $task, $data, $success = true)
    {
        $this->task = $task;
        $this->data = $data;
        $this->success = $success;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    function jsonSerialize()
    {
        return [
            'task'    => $this->task,
            'data'    => $this->data,
            'success' => $this->success,
        ];
    }
}