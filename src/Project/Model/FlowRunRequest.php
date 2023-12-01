<?php

declare(strict_types=1);

namespace Attlaz\Project\Model;

class FlowRunRequest
{
    private string $flowId;
    private array $arguments;
    private ?string $flowRunId;

    public function __construct(string $flowId, array $arguments = [], string $flowRunId = null)
    {
        if (empty($flowId)) {
            throw new \InvalidArgumentException('Flow id cannot be empty');
        }

        $this->flowId = $flowId;
        $this->arguments = $arguments;
        $this->flowRunId = $flowRunId;
    }

    public function getFlowId(): string
    {
        return $this->flowId;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    public function getArgument(string $name): mixed
    {
        return $this->arguments[$name];
    }

    public function getRunId(): string
    {
        return $this->flowRunId;
    }
}
