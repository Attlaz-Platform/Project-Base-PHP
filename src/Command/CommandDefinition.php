<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

class CommandDefinition
{
    public string $task;
    public string $className;
    private array $parameters = [];

    public function addParameter(CommandParameterDefinition $parameterDefinition)
    {
        $this->parameters[] = $parameterDefinition;
    }

    /**
     * @return CommandParameterDefinition[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
