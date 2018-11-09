<?php
declare(strict_types=1);

namespace Attlaz\Project\Command;

class CommandDefinition
{
    public $task;
    public $className;
    private $parameters = [];

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