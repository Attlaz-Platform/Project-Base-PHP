<?php

declare(strict_types=1);

namespace Attlaz\Project\Model;

class FlowRunRequest
{
    public bool $verboseLogging = false;
    private string $flowId;
    private array $arguments;
    private string|null $flowRunId;

    public function __construct(string $flowId, array $arguments = [], string|null $flowRunId = null)
    {
        if (empty($flowId)) {
            throw new \InvalidArgumentException('Flow id cannot be empty');
        }

        $this->flowId = $flowId;
        $this->arguments = [];
        foreach ($arguments as $key => $value) {
            $key = $this->formatArgumentName($key);
            $this->arguments[$key] = $value;
        }


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
        $name = $this->formatArgumentName($name);
        return isset($this->arguments[$name]);
    }

    public function getArgument(string $name): mixed
    {
        $name = $this->formatArgumentName($name);
        return $this->arguments[$name];
    }

    public function getRunId(): string
    {
        return $this->flowRunId;
    }

    private function formatArgumentName(string $input): string
    {
        // Convert PascalCase to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
