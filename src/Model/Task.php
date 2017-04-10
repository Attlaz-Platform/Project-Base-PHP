<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

class Task implements \JsonSerializable
{
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

    function jsonSerialize()
    {
        return [
            'method'    => $this->method,
            'arguments' => $this->arguments,
        ];
    }
}