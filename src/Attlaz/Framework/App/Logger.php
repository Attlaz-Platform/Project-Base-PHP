<?php

namespace Attlaz\Framework\App;

use Psr\Log\LoggerInterface;

class Logger extends \Monolog\Logger implements LoggerInterface
{
    private $globalContext = [];

    public function addRecord($level, $message, array $context = [])
    {
        $context['glob'] = $this->globalContext;

        return parent::addRecord($level, $message, $context);
    }

    public function addGlobalContext(string $key, $value)
    {
        $this->globalContext[$key] = $value;
    }
}