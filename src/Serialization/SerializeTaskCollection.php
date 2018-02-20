<?php
declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\TaskCollection;

class SerializeTaskCollection
{
    public function __invoke(TaskCollection $taskCollection): string
    {
        $data = [];
        foreach ($taskCollection as $task) {
            $data[] = $task->__toString();
        }

        return json_encode($data);
    }
}