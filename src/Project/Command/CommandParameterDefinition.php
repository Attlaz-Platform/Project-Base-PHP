<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

class CommandParameterDefinition
{
    private string $name;
    private string|null $type;
    private bool $required;
    private mixed $default;

    public function __construct(string $name, string|null $type = null, bool $required = true, mixed $default = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->default = $default;
    }

    public static function isCorrectType(mixed $value, self $parameterDefinition): bool
    {
        if (!$parameterDefinition->hasType()) {
            return true;
        }

        $type = $parameterDefinition->getType();

        if (\is_object($value)) {
            return $value instanceof $type;
        }

        $valueType = \gettype($value);
        if ($valueType === 'int' || $valueType === 'integer') {
            $valueType = 'int';
        } elseif ($valueType === 'bool' || $valueType === 'boolean') {
            $valueType = 'bool';
        }
        //        echo $valueType . ' = ' . $parameterDefinition->getType() . \PHP_EOL;

        return $valueType === $type;
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

    /**
     * Coerce a raw argument value into the parameter's declared type when a safe conversion is defined.
     * Currently: ISO 8601 strings (RFC3339_EXTENDED, then ATOM) → \DateTime / \DateTimeImmutable.
     */
    public static function coerce(mixed $value, self $parameterDefinition): mixed
    {
        if (!$parameterDefinition->hasType() || !\is_string($value)) {
            return $value;
        }

        $type = $parameterDefinition->getType();

        if (\is_a($type, \DateTimeInterface::class, true)) {
            return self::parseDateTime($value, $type, $parameterDefinition->getName());
        }

        return $value;
    }

    private static function parseDateTime(string $value, string $type, string $parameterName): \DateTimeInterface
    {
        $concrete = $type === \DateTimeInterface::class ? \DateTimeImmutable::class : $type;

        foreach ([\DateTimeInterface::RFC3339_EXTENDED, \DateTimeInterface::ATOM] as $format) {
            $parsed = $concrete::createFromFormat($format, $value);
            if ($parsed === false) {
                continue;
            }
            $errors = $concrete::getLastErrors();
            if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
                continue;
            }
            return $parsed;
        }

        throw new \InvalidArgumentException(
            'Parameter "' . $parameterName . '" has invalid date format, '
            . 'expected ISO 8601 (RFC3339_EXTENDED or ATOM), got "' . $value . '"'
        );
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

    public function getDefault(): mixed
    {
        return $this->default;
    }
}
