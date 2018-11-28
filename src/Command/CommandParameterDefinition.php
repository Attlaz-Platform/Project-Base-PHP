<?php
declare(strict_types=1);

namespace Attlaz\Project\Command;

class CommandParameterDefinition
{
    private $name;
    private $type;
    private $required = true;
    private $default = null;

    public function __construct(string $name, string $type = null, bool $required = true, $default = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->default = $default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasType(): bool
    {
        return !\is_null($this->type);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getDefault()
    {
        return $this->default;
    }
}
