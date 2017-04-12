<?php

namespace Attlaz\Core\Job;

use Attlaz\Core\Model\JobCommand;

class Log extends JobCommand
{
    public function __invoke(string $message): void
    {
        //TODO: write to log
    }
}