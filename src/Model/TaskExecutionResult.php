<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class TaskExecutionResult implements \JsonSerializable
{
    private string $taskId;
    private $data;
    private bool $success;

    private \DateTime $received;
    private \DateTime $responded;

    public function __construct(string $taskId, $data, bool $success = true)
    {
        $this->taskId = $taskId;
        $this->data = $data;
        $this->success = $success;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
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

    public function setResponded(\DateTime $responded): void
    {
        $this->responded = $responded;
    }

    public function jsonSerialize(): array
    {
        return [
            'task'      => $this->taskId,
            'data'      => $this->data,
            'success'   => $this->success,
            'received'  => $this->received,
            'responded' => $this->responded,
        ];
    }
}
