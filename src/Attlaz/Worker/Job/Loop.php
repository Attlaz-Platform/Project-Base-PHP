<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Framework\Model\Task;
use Attlaz\Worker\Model\JobCommand;

class Loop extends JobCommand
{
    public function __invoke(int $times = 1): void
    {
        if ($times > 0) {
            $task = new Task('loop', ['times' => $times - 1]);

            $this->sendTaskWithoutResult($task);
        }

    }
}