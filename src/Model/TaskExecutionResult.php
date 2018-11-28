<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class TaskExecutionResult implements \JsonSerializable
{
    private $task;
    private $data;
    /** @var bool */
    private $success;

    /** @var  \DateTime */
    private $received;
    /** @var  \DateTime */
    private $responded;

    public function __construct(string $task, $data, $success = true)
    {
        $this->task = $task;
        $this->data = $data;
        $this->success = $success;
    }

    public function getTaskId(): string
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

    public function getReceived(): \DateTime
    {
        return $this->received;
    }

    public function setReceived(\DateTime $received)
    {
        $this->received = $received;
    }

    public function getResponded(): \DateTime
    {
        return $this->responded;
    }

    public function setResponded(\DateTime $responded)
    {
        $this->responded = $responded;
    }

    public function jsonSerialize()
    {
        return [
            'task'      => $this->task,
            'data'      => $this->data,
            'success'   => $this->success,
            'received'  => $this->received,
            'responded' => $this->responded,
        ];
    }
}
