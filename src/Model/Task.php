<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class Task implements \JsonSerializable
{

    private $command;
    private $arguments;

    public function __construct(string $command, array $arguments = [])
    {
        $this->command = $command;
        $this->arguments = $arguments;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    public function getArgument(string $name)
    {
        return $this->arguments[$name];
    }

    function jsonSerialize()
    {
        return [
            'command'   => $this->command,
            'arguments' => $this->arguments,
        ];
    }

    public static function fromArray(array $input): self
    {
        if (!\key_exists('command', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize Task, command is not defined');
        }
        $command = $input['command'];

        $arguments = [];
        if (\key_exists('arguments', $input)) {
            $arguments = $input['arguments'];
        }

        return new self($command, $arguments);
    }
}