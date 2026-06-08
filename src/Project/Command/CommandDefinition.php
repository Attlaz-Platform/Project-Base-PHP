<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

class CommandDefinition
{
    public string $flowId;
    public string $className;
    /** @var CommandParameterDefinition[] */
    private array $parameters = [];

    public function addParameter(CommandParameterDefinition $parameterDefinition): void
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
