<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

use Attlaz\Core\Model\Task;

class SerializeTask
{
    public function __invoke(Task $task): string
    {
        return json_encode($task);
    }
}