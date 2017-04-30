<?php
declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\TaskCollection;

class SerializeTaskCollection
{
    public function __invoke(TaskCollection $taskCollection): string
    {
        $cmd = new SerializeTask();
        $data = [];
        foreach ($taskCollection as $task) {
            $data[] = $cmd->__invoke($task);
        }

        return json_encode($data);
    }
}