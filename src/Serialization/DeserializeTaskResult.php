<?php
declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\TaskResult;

class DeserializeTaskResult
{
    public function __invoke(string $serializedTaskResult): TaskResult
    {
        $taskObject = json_decode($serializedTaskResult, true);
        if (\is_null($taskObject)) {
            throw new \Exception('Unable to deserialize task result, decoded object cannot be null');
        }

        if (!\key_exists('task', $taskObject)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, task is not defined [' . $serializedTaskResult . ']');
        }
        if (!\key_exists('data', $taskObject)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, data is not defined [' . $serializedTaskResult . ']');
        }
        if (!\key_exists('success', $taskObject)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, success is not defined [' . $serializedTaskResult . ']');
        }

        $taskArray = $taskObject['task'];
        if (!is_array($taskArray)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, property task must be serialized as array [' . $serializedTaskResult . ']');
        }

        $cmd = new DeserializeTaskFromArray();
        $task = $cmd->__invoke($taskArray);

        $data = $taskObject['data'];

        $success = $taskObject['success'];
        if (!is_bool($success)) {
            throw new \InvalidArgumentException('Unable to deserialize task result, property success must be serialized as bool [' . $serializedTaskResult . ']');
        }
        $task = new TaskResult($task, $data, $success);

        //TODO: change received, responded to task history objects
//        $received = $taskObject['received'];
//
//        if (!is_array($received)) {
//            throw new \InvalidArgumentException('Unable to deserialize task result, property received must be serialized as array [' . $serializedTaskResult . ']');
//        }
//        $received = DateTimeHelper::deserialize($received);
//        $task->setReceived($received);
//
////
//        $responded = $taskObject['responded'];
//        if (!is_array($responded)) {
//            throw new \InvalidArgumentException('Unable to deserialize task result, property responded must be serialized as array [' . $serializedTaskResult . ']');
//        }
//        $responded = DateTimeHelper::deserialize($responded);
//        $task->setResponded($responded);

        return $task;
    }
}