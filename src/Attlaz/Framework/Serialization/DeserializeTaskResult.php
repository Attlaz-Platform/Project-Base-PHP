<?php
declare(strict_types=1);

namespace Attlaz\Framework\Serialization;

use Attlaz\Framework\Helper\DateTimeHelper;
use Attlaz\Framework\Model\TaskResult;

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
            throw new \InvalidArgumentException('Unable to deserialize task result, property task must be serialized as array');
        }

        $cmd = new DeserializeTaskFromArray();
        $task = $cmd->__invoke($taskArray);

        $data = $taskObject['data'];

        $success = $taskObject['success'];
        if (!is_bool($success)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, property success must be serialized as bool');
        }
        $task = new TaskResult($task, $data, $success);

        $received = $taskObject['received'];

        if (!is_array($received)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, property received must be serialized as array');
        }
        $received = DateTimeHelper::deserialize($received);
        $task->setReceived($received);

//
        $responded = $taskObject['responded'];
        if (!is_array($responded)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, property responded must be serialized as array');
        }
        $responded = DateTimeHelper::deserialize($responded);
        $task->setResponded($responded);

        return $task;

    }
}