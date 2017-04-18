<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Worker\Model\JobCommand;
use Monolog\Logger;

class Log extends JobCommand
{
    public function __invoke(string $message, int $logLevel = Logger::DEBUG): void
    {
        echo 'LOG: ' . $message . ' [' . $logLevel . ']' . PHP_EOL;
    }
}