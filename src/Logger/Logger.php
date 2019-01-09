<?php
declare(strict_types=1);

namespace Attlaz\Project\Logger;

use Psr\Log\LoggerInterface;

class Logger extends \Monolog\Logger implements LoggerInterface
{
    private $globalContext = [];

    public function addRecord($level, $message, array $context = [])
    {
        if (count($this->globalContext) > 0) {
            $context['glob'] = $this->globalContext;
        }

        if ($message instanceof \Throwable) {
            $throwable = $message;
            $message = $throwable->getMessage();
            $context['exception'] = $throwable;
        }

        return parent::addRecord($level, $message, $context);
    }

    public function addGlobalContext(string $key, $value)
    {
        $this->globalContext[$key] = $value;
    }
}
