<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class Task implements \JsonSerializable
{

    private $command;

    public function __construct(string $command)
    {
        $this->command = $command;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    function jsonSerialize()
    {
        return [
            'command' => $this->command,
        ];
    }

    function __toString()
    {
        return json_encode($this->jsonSerialize());
    }

    public static function fromArray(array $input): self
    {
        if (!\key_exists('command', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize Task, command is not defined');
        }
        $command = $input['command'];

        return new self($command);
    }
}