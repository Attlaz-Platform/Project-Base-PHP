<?php

declare(strict_types=1);

namespace Attlaz\Project\Model;

use Attlaz\Model\FlowRun;

class FlowRunRequest
{
    public bool $verboseLogging = false;
    private array $arguments;
    public string|null $flowRunId;

    public function __construct(private readonly FlowRun $flowRun, array $arguments = [])
    {
        $this->arguments = [];
        foreach ($arguments as $key => $value) {
            $key = $this->formatArgumentName($key);
            $this->arguments[$key] = $value;
        }
    }

    public function getFlowRun(): FlowRun
    {
        return $this->flowRun;
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

    private function formatArgumentName(string $input): string
    {
        // Convert PascalCase to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
