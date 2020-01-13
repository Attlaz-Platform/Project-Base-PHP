<?php

declare(strict_types=1);

namespace Attlaz\Project\Model;

class Task implements \JsonSerializable
{
    public $id;
    public $branch;
    public $command;
    public $name;

    public function __construct(string $id, string $branch, string $command, string $name)
    {
        $this->id = $id;
        $this->branch = $branch;
        $this->command = $command;
        $this->name = $name;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function jsonSerialize()
    {
        return [
            'command' => $this->command,
        ];
    }

    public function __toString()
    {
        $result = json_encode($this->jsonSerialize());
        if (!\is_string($result)) {
            throw new \Exception('Unable to serialize task');
        }

        return $result;
    }

    public static function fromArray(array $input): self
    {
        if (!\key_exists('id', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize Task, id is not defined');
        }
        $id = $input['id'];

        if (!\key_exists('branch', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize Task, branch is not defined');
        }
        $branch = $input['branch'];

        if (!\key_exists('command', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize Task, command is not defined');
        }
        $command = $input['command'];

        if (!\key_exists('name', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize Task, name is not defined');
        }
        $name = $input['name'];

        return new self($id, $branch, $command, $name);
    }
}
