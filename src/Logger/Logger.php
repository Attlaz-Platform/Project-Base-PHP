<?php

declare(strict_types=1);

namespace Attlaz\Project\Logger;

use Attlaz\Project\Exception\RuntimeException;
use Psr\Log\LoggerInterface;

class Logger extends \Monolog\Logger implements LoggerInterface
{
    private const CONTEXT_ERROR_PREFIX = 'error';


    public function emergency($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(static::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(static::ALERT, $message, $context);
    }

    public function critical($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(static::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(static::ERROR, $message, $context);
    }

    public function warning($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(static::WARNING, $message, $context);
    }

    public function notice($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(static::NOTICE, $message, $context);
    }

    public function info($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(static::INFO, $message, $context);
    }

    public function debug($message, array $context = array()): void
    {
        list($message, $context) = $this->formatMessageAndContext($message, $context);
        $this->addRecord(static::DEBUG, $message, $context);
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
        while (\key_exists($prefix, $context)) {
            $errCount++;
            $prefix = $prefix . ' ' . $errCount;
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

//    private function removeSecrets($input)
//    {
//        if (\is_string($input)) {
//            return \str_replace(['/var/attlaz/'], '***', $input);
//        }
//        if (\is_array($input)) {
//
//            array_walk($input, function (&$value, &$key) {
//                $key = $this->removeSecrets($key);
//                $value = $this->removeSecrets($value);
//            });
//            return $input;
//
//        }
//        if (\is_object($input)) {
//            $result = [];
//
////            $vars = get_object_vars($input);
////            \var_dump($input);
////            \var_dump($vars);
////            die('--');
//            foreach ($input as $key => $value) {
//                $key = $this->removeSecrets($key);
//                $value = $this->removeSecrets($value);
//
//                $result[$key] = $value;
//            }
//            \var_dump($result);
//            die('--');
//            return $result;
//        }
//
//        return $input;
//    }
}
