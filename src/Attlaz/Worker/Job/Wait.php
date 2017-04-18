<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Worker\Model\JobCommand;

class Wait extends JobCommand
{
    public function __invoke(int $seconds): void
    {
        sleep($seconds);
    }
}