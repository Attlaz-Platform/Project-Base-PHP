<?php
declare(strict_types=1);

namespace Attlaz\Framework\Serialization;

use Attlaz\Framework\Model\Task;

class SerializeTask
{
    public function __invoke(Task $task): string
    {
        return json_encode($task);
    }
}