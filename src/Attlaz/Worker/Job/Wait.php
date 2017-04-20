<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Framework\Helper\DateTimeHelper;
use Attlaz\Worker\Model\JobCommand;

class Wait extends JobCommand
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