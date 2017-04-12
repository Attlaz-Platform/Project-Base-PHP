<?php

namespace Attlaz\Core\Job;

use Attlaz\Core\Model\JobCommand;
use Monolog\Logger;

class Log extends JobCommand
{
    public function __invoke(string $message, int $logLevel = Logger::DEBUG): void
    {
        echo 'LOG: ' . $message . ' [' . $logLevel . ']' . PHP_EOL;
    }
}