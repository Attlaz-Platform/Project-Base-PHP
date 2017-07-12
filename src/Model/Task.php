<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

class Task implements \JsonSerializable
{
    private $id;
    private $method;
    private $arguments;

    public function __construct(string $method, array $arguments = [])
    {
        $this->method = $method;
        $this->arguments = $arguments;
    }

    public function getMethod(): string
    {
        return $this->method;
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

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    function jsonSerialize()
    {
        return [
            'id'        => $this->id,
            'method'    => $this->method,
            'arguments' => $this->arguments,
        ];
    }
}