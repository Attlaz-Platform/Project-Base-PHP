<?php

namespace Attlaz\Worker\Job;

use Attlaz\Worker\Model\JobCommand;

class Ping extends JobCommand
{
    public function __invoke(string $input): string
    {
        return 'Pong [' . $input . ']';
    }
}