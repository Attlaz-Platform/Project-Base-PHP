<?php
declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\Task;

class SerializeTask
{
    public function __invoke(Task $task): string
    {
        return json_encode($task);
    }
}