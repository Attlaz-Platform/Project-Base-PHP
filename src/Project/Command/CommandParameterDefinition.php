<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

class CommandParameterDefinition
{
    private string $name;
    private string|null $type;
    private bool $required = true;
    private mixed $default = null;

    public function __construct(string $name, string|null $type = null, bool $required = true, mixed $default = null)
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

    public function __toString(): string
    {
        $parameterString = $this->getName();

        if ($this->hasType()) {
            $parameterString .= ' <type \'' . $this->getType() . '\'>';
        } else {
            $parameterString .= ' <type not defined>';
        }

        if ($this->isRequired()) {
            $parameterString .= ' [required]';
        } else {
            $default = $this->getDefault();
            if (\is_bool($default)) {
                $default = $default ? 'true' : 'false';
            }

            $parameterString .= ' [optional, default: ' . $default . ']';
        }

        return $parameterString;
    }

    public static function isCorrectType(mixed $value, self $parameterDefinition): bool
    {
        if (!$parameterDefinition->hasType()) {
            return true;
        }

        $valueType = \gettype($value);
        if ($valueType === 'int' || $valueType === 'integer') {
            $valueType = 'int';
        } elseif ($valueType === 'bool' || $valueType === 'boolean') {
            $valueType = 'bool';
        }
        //        echo $valueType . ' = ' . $parameterDefinition->getType() . \PHP_EOL;

        return $valueType === $parameterDefinition->getType();
        //        if ($valueType !== $parameterDefinition->getType()) {
        //        }

        //        switch ($parameter->getType()) {
        //            case 'int':
        //                if (!\is_int($value)) {
        //                    throw new \Exception('Parameter "' . $parameter->getName() . '"
        // has invalid type, type "' .
        // ($parameter->hasType() ? $parameter->getType() : 'undefined') . '" expected');
        //                }
        //                break;
        //            case 'string':
        //                if (!\is_string($value)) {
        //                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
        //                }
        //                break;
        //            case 'array':
        //                if (!\is_array($value)) {
        //                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
        //                }
        //                break;
        //            case 'bool':
        //                if (!\is_bool($value)) {
        //                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
        //                }
        //                break;
        //            default:
        //                $this->logger->warning('Unknown parameter type "' . $type . '"');
        //        }
    }
}
