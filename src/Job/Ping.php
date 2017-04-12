<?php

namespace Attlaz\Core\Job;

use Attlaz\Core\Model\JobCommand;

class Ping extends JobCommand
{
    public function __invoke(string $input): string
    {
        return 'Pong [' . $input . ']';
    }
}