<?php

namespace Attlaz\Worker\Job;

use Attlaz\Framework\Helper\DateTimeHelper;
use Attlaz\Worker\Model\JobCommand;

class Wait2 extends JobCommand
{
    public function __invoke(int $seconds): array
    {
        $start = DateTimeHelper::getNow();
        sleep($seconds);

        return [
            'start' => $start,
            'end'   => DateTimeHelper::getNow(),
        ];

    }
}