<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

use Attlaz\Core\Model\TaskResult;

class DeserializeTaskResult
{
    public function __invoke(string $serializedTaskResult): TaskResult
    {
        $taskObject = json_decode($serializedTaskResult, true);
        if (!isset($taskObject['task']) || !isset($taskObject['data']) || !isset($taskObject['success'])) {
            throw new \InvalidArgumentException('Unable to deserialize task result, properties task,data and success are required');
        }

        $taskArray = $taskObject['task'];
        if (!is_array($taskArray)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, properties task must be serialized as array');
        }

        $cmd = new DeserializeTaskFromArray();
        $task = $cmd->__invoke($taskArray);

        $data = $taskObject['data'];
        $success = $taskObject['success'];

        if (!is_bool($success)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, properties success must be serialized as bool');
        }

        $task = new TaskResult($task, $data, $success);

        return $task;

    }
}