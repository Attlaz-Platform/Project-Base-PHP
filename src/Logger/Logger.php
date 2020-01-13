<?php

declare(strict_types=1);

namespace Attlaz\Project\Logger;

use Attlaz\Project\Exception\RuntimeException;
use Psr\Log\LoggerInterface;

class Logger extends \Monolog\Logger implements LoggerInterface
{
    private $globalContext = [];

    private const CONTEXT_EXCEPTION_PREFIX = 'exception';

    public function addRecord($level, $message, array $context = [])
    {
        if (count($this->globalContext) > 0) {
            $context['glob'] = $this->globalContext;
        }
        foreach ($context as $key => $value) {
            if ($value instanceof RuntimeException) {
                $context = $this->mergeExceptionContext($value, $context, false);
            }
        }

        if ($message instanceof RuntimeException) {
            $exception = $message;
            $message = $exception->getMessage();

            $context = $this->mergeExceptionContext($exception, $context, true);
        } elseif ($message instanceof \Throwable) {
            $throwable = $message;
            $message = $throwable->getMessage();
            $context[self::CONTEXT_EXCEPTION_PREFIX] = $throwable;
        }

        return parent::addRecord($level, $message, $context);
    }

    private function mergeExceptionContext(
        RuntimeException $runtimeException,
        array $context,
        bool $appendExceptionToContext
    ): array {
        if ($appendExceptionToContext) {
            $context[self::CONTEXT_EXCEPTION_PREFIX] = $runtimeException;
        }

        return \array_merge($context, $runtimeException->getContext());
    }

    public function addGlobalContext(string $key, $value)
    {
        $this->globalContext[$key] = $value;
    }
}
