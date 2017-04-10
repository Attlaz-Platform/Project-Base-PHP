<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

use Attlaz\Core\Model\TaskResult;

class SerializeTaskResult
{
    public function __invoke(TaskResult $taskResult): string
    {
        return json_encode($taskResult);
    }
}