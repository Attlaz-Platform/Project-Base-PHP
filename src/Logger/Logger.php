<?php

declare(strict_types=1);

namespace Attlaz\Project\Logger;

use Attlaz\Project\Exception\RuntimeException;
use Monolog\Level;
use Psr\Log\LoggerInterface;

class Logger extends \Monolog\Logger implements LoggerInterface
{
    private const CONTEXT_ERROR_PREFIX = 'error';


    public function emergency($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(\Monolog\Level::Emergency, $message, $context);
    }

    public function alert($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(\Monolog\Level::Alert, $message, $context);
    }

    public function critical($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(\Monolog\Level::Critical, $message, $context);
    }

    public function error($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(\Monolog\Level::Error, $message, $context);
    }

    public function warning($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(\Monolog\Level::Warning, $message, $context);
    }

    public function notice($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(\Monolog\Level::Notice, $message, $context);
    }

    public function info($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(\Monolog\Level::Info, $message, $context);
    }

    public function debug($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(\Monolog\Level::Debug, $message, $context);
    }


    public function log($level, $message, array $context = []): void
    {
        if (!is_int($level) && !is_string($level)) {
            throw new \InvalidArgumentException('$level is expected to be a string or int');
        }

        $level = static::toMonologLevel($level);
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord($level, $message, $context);
    }

    private function formatMessageAndContext($message, array $context): array
    {
        /**
         * Format exceptions
         */
        if ($message instanceof RuntimeException) {
            $exception = $message;
            $message = $exception->getMessage();

            $context = $this->mergeExceptionContext($exception, $context, true);
        } elseif ($message instanceof \Throwable) {
            $throwable = $message;
            $message = $throwable->getMessage();
            $context[$this->getErrorPrefix($context)] = $throwable;
        } else {
            $message = (string)$message;
        }
        /**
         * Format context exceptions
         */
        foreach ($context as $key => $value) {
            if ($value instanceof RuntimeException) {
                $context = $this->mergeExceptionContext($value, $context, false);
            }
        }
//        $message = $this->removeSecrets($message);
//        $context = $this->removeSecrets($context);

        return [$message, $context];
    }

    private function getErrorPrefix(array $context): string
    {
        $prefix = self::CONTEXT_ERROR_PREFIX;
        $errCount = 1;
        while (\array_key_exists($prefix, $context)) {
            $errCount++;
            $prefix .= ' ' . $errCount;
        }
        return $prefix;
    }


    private function mergeExceptionContext(RuntimeException $runtimeException, array $context, bool $appendExceptionToContext): array
    {
        if ($appendExceptionToContext) {
            $context[$this->getErrorPrefix($context)] = $runtimeException;
        }

        return \array_merge($context, $runtimeException->getContext());
    }
}
