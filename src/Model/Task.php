<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

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
            'method'    => $this->method,
            'arguments' => $this->arguments,
        ];
    }

    public static function fromArray(array $input): self
    {
        if (!\key_exists('arguments', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize task, arguments is not defined');
        }
        if (!\key_exists('method', $input)) {
            throw new \InvalidArgumentException('Unable to deserialize task, method is not defined');
        }

        $method = $input['method'];
        $arguments = $input['arguments'];

        return new self($method, $arguments);
    }
}