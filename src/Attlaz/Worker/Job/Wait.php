<?php

namespace Attlaz\Core\Job;

use Attlaz\Core\Model\JobCommand;
use Monolog\Logger;

class Wait extends JobCommand
{
    public function __invoke(int $seconds): void
    {
        sleep($seconds);
    }
}