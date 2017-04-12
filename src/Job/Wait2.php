<?php

namespace Attlaz\Core\Job;

use Attlaz\Core\Helper\DateTimeHelper;
use Attlaz\Core\Model\JobCommand;

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