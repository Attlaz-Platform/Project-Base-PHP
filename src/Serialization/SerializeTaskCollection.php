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

        $result = json_encode($data);

        if (!\is_string($result)) {
            throw new \Exception('Unable to serialize task collection');
        }

        return $result;
    }
}
