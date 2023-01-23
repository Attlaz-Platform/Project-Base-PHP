<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class FlowRunResult implements \JsonSerializable
{
    private string $flowId;
    private mixed $data;
    private bool $success;

    private \DateTime|null $received = null;
    private \DateTime|null $responded = null;

    public function __construct(string $flowId, mixed $data, bool $success = true)
    {
        $this->flowId = $flowId;
        $this->data = $data;
        $this->success = $success;
    }

    public function getFlowId(): string
    {
        return $this->flowId;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getSuccess(): bool
    {
        return $this->success;
    }

    public function getReceived(): \DateTime|null
    {
        return $this->received;
    }

    public function setReceived(\DateTime $received): void
    {
        $this->received = $received;
    }

    public function getResponded(): \DateTime|null
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
            'flow'      => $this->flowId,
            'data'      => $this->data,
            'success'   => $this->success,
            'received'  => $this->received,
            'responded' => $this->responded,
        ];
    }
}
